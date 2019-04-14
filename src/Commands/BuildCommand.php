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
use Vectory;
use Vectory\Services\VectorDefinitionGeneratorInterface;
use Vectory\ValueObjects\VectorDefinitionInterface;
use Yay\Engine;

final class BuildCommand extends Command
{
    private const DIR_BASE = __DIR__.'/../..';

    private const DIR_DIST = self::DIR_BASE.'/dist';
    private const DIR_SRC = self::DIR_BASE.'/src';
    private const DIR_TESTS = self::DIR_BASE.'/tests';
    private const DIR_PHPUNIT = self::DIR_TESTS.'/PhpUnit';
    private const DIR_PHPBENCH = self::DIR_TESTS.'/PhpBench';
    private const DIR_VENDOR_BIN = self::DIR_BASE.'/vendor/bin';

    private const DIR_YAY = self::DIR_SRC.'/Macros';

    private const DIR_SHARED_MACROS = self::DIR_YAY.'/Includes';
    private const DIR_DELIVERABLES = self::DIR_SRC.'/Deliverables';

    private const FILE_EXTENSION_YAY = '.yay';
    private const FILE_EXTENSION_PHP = '.php';

    private const PATH_FORMAT_YAY = self::DIR_YAY.'/%s'.self::FILE_EXTENSION_YAY;
    private const PATH_FORMAT_DIST = self::DIR_DIST.'/%s'.self::FILE_EXTENSION_PHP;
    private const PATH_FORMAT_PHPUNIT = self::DIR_PHPUNIT.'/%sTest'.self::FILE_EXTENSION_PHP;
    private const PATH_FORMAT_PHPBENCH = self::DIR_PHPBENCH.'/%sBench'.self::FILE_EXTENSION_PHP;

    private const PHP_PREAMBLE = "<?php\n";
    private const PHP_PREAMBLE_REGEX = '~^<\\?php\\s+~';

    private const MACRO_FORMAT_CONTEXT = '$(macro) { $[%s] } >> { %s }';
    private const MACRO_FORMAT_ENABLE =
        <<<'YAY'
$(macro) { $<%1$s> $({...} as body) } >> { $(body) }
$(macro) {
  $<!%1$s> $({...} as body)
  $(_() as __context_Nop)
} >> function (\Yay\Ast $ast) {
    $token = new \Yay\Token(\T_CONSTANT_ENCAPSED_STRING, ' ');
    $ast->append(new \Yay\Ast('__context_Nop', $token));
} >> {
    $(__context_Nop)
}
YAY;
    private const MACRO_FORMAT_IGNORE =
        <<<'YAY'
$(macro) { $<!%1$s> $({...} as body) } >> { $(body) }
$(macro) {
  $<%1$s> $({...} as body)
  $(_() as __context_Nop)
} >> function (\Yay\Ast $ast) {
    $token = new \Yay\Token(\T_CONSTANT_ENCAPSED_STRING, ' ');
    $ast->append(new \Yay\Ast('__context_Nop', $token));
} >> {
    $(__context_Nop)
}
YAY;

    private const MAIN_MACRO_DIST = 'Vector';
    private const MAIN_MACRO_PHPUNIT = 'VectorTest';
    private const MAIN_MACRO_PHPBENCH = 'VectorBench';

    private const PROCESS_PHP_CS_FIXER = [
        self::DIR_VENDOR_BIN.'/php-cs-fixer',
        'fix',
        '--config='.self::DIR_BASE.'/.php_cs',
        '--using-cache=no',
        self::DIR_DIST,
        self::DIR_PHPUNIT,
        self::DIR_PHPBENCH,
    ];

    private /* VectorDefinitionGeneratorInterface */ $vectorDefinitionGenerator;
    private /* LoggerInterface */ $logger;
    private /* Parser */ $parser;
    private /* PrettyPrinter */ $prettyPrinter;
    private /* array */ $tasks = [];

    public function __construct(
        VectorDefinitionGeneratorInterface $vectorDefinitionGenerator,
        string $name = null
    ) {
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
    }

    private function cleanDist(): void
    {
        foreach ([self::DIR_DIST, self::DIR_PHPUNIT, self::DIR_PHPBENCH] as $path) {
            $this->logger->debug('Cleaning '.\realpath($path));
            foreach (self::generateFiles($path) as $pathname) {
                \unlink($pathname);
            }
        }
    }

    private function copyDeliverables(): void
    {
        $this->logger->info(
            'Copying deliverables from '.\realpath(self::DIR_DELIVERABLES)
        );
        foreach (self::generateFiles(self::DIR_DELIVERABLES) as $filename => $pathname) {
            \copy($pathname, self::DIR_DIST.'/'.$filename);
        }
    }

    private function processTask(VectorDefinitionInterface $vectorDefinition): void
    {
        Vectory::setDefinition($vectorDefinition);
        $target = $vectorDefinition->getClassName();
        $this->logger->info('Building '.$target);

        foreach ([
            self::MAIN_MACRO_DIST => self::PATH_FORMAT_DIST,
            self::MAIN_MACRO_PHPUNIT => self::PATH_FORMAT_PHPUNIT,
            self::MAIN_MACRO_PHPBENCH => self::PATH_FORMAT_PHPBENCH,
        ] as $mainMacro => $pathFormat) {
            $concatenatedMacros =
                $this->concatenateMacros($vectorDefinition, $mainMacro);
            $targetPath = \sprintf($pathFormat, $target);

            $this->logger->debug('Expanding to '.$target);
            $expansion = (new Engine())->expand(
                $concatenatedMacros,
                $targetPath,
                Engine::GC_ENGINE_DISABLED
            );

            $this->logger->debug('Built '.\realpath($targetPath));
            \file_put_contents($targetPath, $expansion);
        }
    }

    private function concatenateMacros(
        VectorDefinitionInterface $vectorDefinition,
        string $mainMacro
    ): string {
        static $sharedMacros = [];

        if (!$sharedMacros) {
            foreach (self::generateFiles(self::DIR_SHARED_MACROS) as $filename => $pathname) {
                $sharedMacros[$pathname] =
                    \substr($filename, 0, -\strlen(self::FILE_EXTENSION_YAY));
            }
        }

        $fqn = $vectorDefinition->getFullyQualifiedClassName();
        $concatenatedMacros = [\sprintf(self::MACRO_FORMAT_CONTEXT, 'Fqn', $fqn)];
        foreach ($vectorDefinition->export() as $name => $value) {
            if (\is_string($value)) {
                $encodedValue = '';
                for ($i = 0; $i < \strlen($value); ++$i) {
                    $chr = $value[$i];
                    $ord = \ord($chr);
                    if ('\\' === $chr || $ord >= 0x20 && $ord <= 0x7f) {
                        $encodedValue .= $chr;
                    } else {
                        $encodedValue .= '\\x'.\dechex(\ord($chr));
                    }
                }
                $encodedValue = '"'.$encodedValue.'"';
            } else {
                $encodedValue = \json_encode($value);
            }
            $concatenatedMacros[] =
                \sprintf(self::MACRO_FORMAT_CONTEXT, \ucfirst($name), $encodedValue);
        }

        $flags = [
            'HasBitArithmetic' => $vectorDefinition->hasBitArithmetic(),
            'HasMinimumMaximum' => $vectorDefinition->isInteger() &&
            $vectorDefinition->getBytesPerElement() < 8,
            'Nullable' => $vectorDefinition->isNullable(),
            'Signed' => $vectorDefinition->isInteger() &&
            $vectorDefinition->isSigned(),
            'Boolean' => $vectorDefinition->isBoolean(),
            'Integer' => $vectorDefinition->isInteger(),
            'String' => $vectorDefinition->isString(),
        ];

        for ($i = 1; $i <= 8; ++$i) {
            if ($vectorDefinition->isBoolean()) {
                $flags['Takes'.$i] = 1 === $i;
            } else {
                $flags['Takes'.$i] =
                    $i === ($vectorDefinition->getBytesPerElement() ?? 1);
            }
        }

        foreach ($flags as $name => $isEnabled) {
            $format = $isEnabled ? self::MACRO_FORMAT_ENABLE : self::MACRO_FORMAT_IGNORE;
            $concatenatedMacros[] = \sprintf($format, $name, true);
        }

        $resolvedMacros = $sharedMacros;
        $resolvedMacros[\sprintf(self::PATH_FORMAT_YAY, $mainMacro)] = $mainMacro;

        foreach ($resolvedMacros as $path => $macro) {
            $this->logger->debug('Concatenating '.$macro);
            $macroContents = \file_get_contents($path);
            \assert(\is_string($macroContents));
            $macroContentsSansPhpPreamble =
                \preg_replace(self::PHP_PREAMBLE_REGEX, '', $macroContents, 1);
            \assert(\is_string($macroContentsSansPhpPreamble));
            $concatenatedMacros[] = \trim($macroContentsSansPhpPreamble);
        }

        return self::PHP_PREAMBLE."\n".\implode("\n", $concatenatedMacros)."\n";
    }

    private function prettyPrintFiles(): void
    {
        $this->logger->info('Pretty-printing');
        foreach ([self::DIR_DIST, self::DIR_PHPUNIT] as $path) {
            foreach (self::generateFiles($path) as $filename => $pathname) {
                $this->logger->debug('Pretty-printing '.$filename);
                $code = \file_get_contents($pathname);
                $ast = $this->parser->parse($code);
                $code = $this->prettyPrinter->prettyPrintFile($ast);
                \file_put_contents($pathname, $code);
            }
        }
    }

    private function lintFiles(): void
    {
        $this->logger->info('Linting');
        (new Process(self::PROCESS_PHP_CS_FIXER))->run();
    }

    private static function generateFiles(string $directory): iterable
    {
        $directoryIterator = new \RecursiveDirectoryIterator($directory);
        foreach (new \RecursiveIteratorIterator($directoryIterator) as $fileInfo) {
            \assert($fileInfo instanceof \SplFileInfo);
            if ($fileInfo->isFile()) {
                yield $fileInfo->getFilename() => $fileInfo->getPathname();
            }
        }
    }
}
