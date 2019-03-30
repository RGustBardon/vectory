<?php

declare(strict_types=1);

/*
 * This file is part of the Vectory package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vectory\Commands;

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Yay\Engine;
use Vectory;
use Vectory\Services\VectorDefinitionGenerator;
use Vectory\Services\VectorDefinitionGeneratorInterface;
use Vectory\ValueObjects\VectorDefinitionInterface;

final class BuildCommand extends Command
{
    private const DIR_BASE = __DIR__.'/../..';

    private const DIR_DIST = self::DIR_BASE.'/dist';
    private const DIR_SRC = self::DIR_BASE.'/src';
    private const DIR_TESTS = self::DIR_BASE.'/tests';
    private const DIR_VENDOR_BIN = self::DIR_BASE.'/vendor/bin';

    private const DIR_YAY = self::DIR_SRC.'/Macros';

    private const DIR_SHARED_MACROS = self::DIR_YAY.'/Includes';
    private const DIR_DELIVERABLES = self::DIR_SRC.'/Deliverables';

    private const FILE_EXTENSION_YAY = '.yay';
    private const FILE_EXTENSION_PHP = '.php';

    private const GLOB_SHARED_MACROS = self::DIR_SHARED_MACROS.'/*'.self::FILE_EXTENSION_YAY;

    private const PATH_FORMAT_YAY = self::DIR_YAY.'/%s'.self::FILE_EXTENSION_YAY;
    private const PATH_FORMAT_DIST = self::DIR_DIST.'/%s'.self::FILE_EXTENSION_PHP;
    private const PATH_FORMAT_TESTS = self::DIR_TESTS.'/%s'.self::FILE_EXTENSION_PHP;
    
    private const PHP_PREAMBLE = "<?php\n";
    private const PHP_PREAMBLE_REGEX = '~^<\\?php(?=\\R)~';

    private const MACRO_FORMAT_CONTEXT = '$(macro) { $[%s] } >> { %s }';
    
    private const MAIN_MACRO_DIST = 'Vector';
    private const MAIN_MACRO_TESTS = 'VectorTest';

    private const PROCESS_PHP_CS_FIXER = [
        self::DIR_VENDOR_BIN.'/php-cs-fixer',
        'fix',
        '--config='.self::DIR_BASE.'/.php_cs',
        '--using-cache=no',
        self::DIR_DIST,
        self::DIR_TESTS,
    ];

    private const PROCESS_PHPSTAN = [
        self::DIR_VENDOR_BIN.'/phpstan',
        'analyse',
        '--configuration='.self::DIR_BASE.'/phpstan.neon',
        '--level=max',
        '--no-progress',
        self::DIR_DIST,
        self::DIR_TESTS,
    ];

    private /* VectorDefinitionGeneratorInterface */ $vectorDefinitionGenerator;
    private /* LoggerInterface */ $logger;
    private /* Parser */ $parser;
    private /* PrettyPrinter */ $prettyPrinter;
    private /* array */ $tasks = [];
    
    public function __construct(
        VectorDefinitionGeneratorInterface $vectorDefinitionGenerator,
        string $name = null
    )
    {
        parent::__construct($name);
        
        $this->vectorDefinitionGenerator = $vectorDefinitionGenerator;
    }

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds the project');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->prettyPrinter = new PrettyPrinter\Standard();

        $this->cleanDist();
        $this->copyDeliverables();

        $this->logger->debug('Processing definitions');
        foreach ($this->vectorDefinitionGenerator->generate() as $vectorDefinition) {
            $this->processTask($vectorDefinition);
        }

        $this->prettyPrintFiles();
        $this->lintFiles();
        $this->analyzeFiles();
    }

    private function cleanDist(): void
    {
        foreach ([self::DIR_DIST, self::DIR_TESTS] as $path)  {
            $this->logger->debug('Cleaning '.\realpath($path));
            \array_map('\\unlink', \glob($path.'/*'));
        }
    }

    private function copyDeliverables(): void
    {
        $this->logger->info(
            'Copying deliverables from '.\realpath(self::DIR_DELIVERABLES)
        );
        $globIterator = new \GlobIterator(
            self::DIR_DELIVERABLES.'/*',
            \FilesystemIterator::KEY_AS_FILENAME
        );
        foreach ($globIterator as $key => $item) {
            \copy($item->getPathname(), self::DIR_DIST.'/'.$key);
        }
    }

    private function processTask(VectorDefinitionInterface $vectorDefinition): void
    {
        Vectory::setDefinition($vectorDefinition);

        $target = $vectorDefinition->getClassName();
        $this->logger->info('Building '.$target);
        
        foreach ([
            self::MAIN_MACRO_DIST => self::PATH_FORMAT_DIST,
            self::MAIN_MACRO_TESTS => self::PATH_FORMAT_TESTS,
        ] as $mainMacro => $pathFormat) {
            $contactenatedMacros =
                $this->concatenateMacros($mainMacro, $vectorDefinition);
            $targetPath = \sprintf($pathFormat, $target);
    
            $this->logger->debug('Expanding to '.$target);
            $expansion = (new Engine())->expand(
                $contactenatedMacros,
                $targetPath,
                Engine::GC_ENGINE_DISABLED
            );
    
            $this->logger->debug('Built '.\realpath($targetPath));
            \file_put_contents($targetPath, $expansion);
        }
    }

    private function concatenateMacros(string $mainMacro): string
    {
        static $sharedMacros = [];

        if (!$sharedMacros) {
            $globIterator = new \GlobIterator(
                self::GLOB_SHARED_MACROS,
                \FilesystemIterator::KEY_AS_FILENAME
            );
            foreach ($globIterator as $key => $item) {
                $sharedMacros[$item->getPathname()] =
                    \substr($key, 0, -\strlen(self::FILE_EXTENSION_YAY));
            }
        }

        $concatenatedMacros = [self::PHP_PREAMBLE];
        $resolvedMacros = $sharedMacros;
        $resolvedMacros[\sprintf(self::PATH_FORMAT_YAY, $mainMacro)] = $mainMacro;

        foreach ($resolvedMacros as $path => $macro) {
            $this->logger->debug('Concatenating '.$macro);
            $macroContents = \file_get_contents($path);
            $macroContentsSansPhpPreamble =
                \preg_replace(self::PHP_PREAMBLE_REGEX, '', $macroContents, 1);
            $concatenatedMacros[] = \trim($macroContentsSansPhpPreamble);
        }

        return \implode("\n", $concatenatedMacros)."\n";
    }

    private function prettyPrintFiles(): void
    {
        $this->logger->info('Pretty-printing');
        foreach ([self::DIR_DIST, self::DIR_TESTS] as $path) {
            foreach (\glob($path.'/*') as $file) {
                $this->logger->debug('Pretty-printing '.\realpath($file));
                $code = \file_get_contents($file);
                $ast = $this->parser->parse($code);
                $code = $this->prettyPrinter->prettyPrintFile($ast);
                \file_put_contents($file, $code);
            }
        }
    }

    private function lintFiles(): void
    {
        $this->logger->info('Linting');
        (new Process(self::PROCESS_PHP_CS_FIXER))->run();
    }

    private function analyzeFiles(): void
    {
        $this->logger->info('Analyzing');
        $process = (new Process(self::PROCESS_PHPSTAN));
        $process->run();
        $output = \trim($process->getOutput());
        if ('' !== $output) {
            echo $output, "\n";
        }
    }
}
