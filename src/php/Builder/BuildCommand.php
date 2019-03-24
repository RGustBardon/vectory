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

namespace Vectory\Builder;

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Yay\Engine;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Process\Process;

final class BuildCommand extends Command
{
    private const DIR_BASE = __DIR__.'/../../..';

    private const DIR_DIST = self::DIR_BASE.'/dist';
    private const DIR_SRC = self::DIR_BASE.'/src';
    private const DIR_VENDOR_BIN = self::DIR_BASE.'/vendor/bin';
    
    private const DIR_CONFIG = self::DIR_SRC.'/config';
    private const DIR_PHP = self::DIR_SRC.'/php';
    private const DIR_YAY = self::DIR_SRC.'/yay';
    
    private const DIR_SHARED_MACROS = self::DIR_YAY.'/shared';
    private const DIR_COPIED_VERBATIM = self::DIR_PHP.'/CopiedVerbatim';
    
    private const FILE_BUILD = self::DIR_CONFIG.'/build.json';
    
    private const FILE_EXTENSION_YAY = '.yay';
    private const FILE_EXTENSION_PHP = '.php';
    
    private const GLOB_COPIED_VERBATIM = self::DIR_COPIED_VERBATIM.'/*';
    private const GLOB_DIST = self::DIR_DIST.'/*';
    private const GLOB_SHARED_MACROS = self::DIR_SHARED_MACROS.'/*'.self::FILE_EXTENSION_YAY;
    
    private const PATH_FORMAT_YAY = self::DIR_YAY.'/%s'.self::FILE_EXTENSION_YAY;
    private const PATH_FORMAT_PHP = self::DIR_DIST.'/%s'.self::FILE_EXTENSION_PHP;
    
    private const JSON_KEY_MACROS = 'macros';
    private const JSON_KEY_TARGET = 'target';
    private const JSON_KEY_CONTEXT = 'context';
    
    private const PHP_PREAMBLE = "<?php\n";
    private const PHP_PREAMBLE_REGEX = "~^<\\?php(?=\\R)~";
    
    private const MACRO_FORMAT_CONTEXT = '$(macro) { $[%s] } >> { %s }';
    
    private const PROCESS_PHP_CS_FIXER = [
        self::DIR_VENDOR_BIN.'/php-cs-fixer',
        'fix',
        '--config='.self::DIR_CONFIG.'/.php_cs',
        '--using-cache=no',
        self::DIR_DIST,
    ];
    
    private /* LoggerInterface */ $logger;
    private /* Parser */ $parser;
    private /* Engine */ $yay;
    
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
        $this->prettyPrinter = new PrettyPrinter\Standard;
        $this->yay = new Engine();
        
        $this->cleanDist();
        $this->copyFiles();
        
        $this->loadTasks();
        
        $this->logger->debug('Processing tasks');
        foreach ($this->tasks as $task) {
            $this->processTask($task);
        }

        $this->prettyPrintFiles();
        $this->lintFiles();
    }
    
    private function cleanDist(): void
    {
        $this->logger->debug('Cleaning '.\realpath(self::DIR_DIST));
        \array_map('\\unlink', \glob(self::GLOB_DIST));
    }
    
    private function copyFiles(): void
    {
        $this->logger->info(
            'Copying files from '.\realpath(self::DIR_COPIED_VERBATIM)
        );
        $globIterator = new \GlobIterator(
            self::GLOB_COPIED_VERBATIM,
            \FilesystemIterator::KEY_AS_FILENAME
        );
        foreach ($globIterator as $key => $item) {
            \copy($item->getPathname(), self::DIR_DIST.'/'.$key);
        }
    }
    
    private function loadTasks(): void
    {
        $this->logger->debug('Loading tasks from '.\realpath(self::FILE_BUILD));
        $json = \file_get_contents(self::FILE_BUILD);
        $this->tasks = \json_decode($json, true);
    }
    
    private function processTask(array $task): void
    {
        $macros = $task[self::JSON_KEY_MACROS];
        $target = $task[self::JSON_KEY_TARGET];
        $context = $task[self::JSON_KEY_CONTEXT];
        
        
        $this->logger->info('Building '.$target);
        
        $contactenatedMacros = $this->concatenateMacros($macros, $context);
        $targetPath = \sprintf(self::PATH_FORMAT_PHP, $target);
        
        $this->logger->debug('Expanding to '.$target);
        $GLOBALS['__context'] = $context;
        $expansion = $this->yay->expand(
            $contactenatedMacros,
            $targetPath,
            Engine::GC_ENGINE_DISABLED
        );
        unset($GLOBALS['__context']);
        
        $this->logger->debug('Built '.\realpath($targetPath));
        \file_put_contents($targetPath, $expansion);
    }

    private function concatenateMacros(array $macros, array $context): string
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
        
        foreach ($context as $name => $value) {
            $concatenatedMacros[] =
                \sprintf(self::MACRO_FORMAT_CONTEXT, $name, \json_encode($value));
        }
        
        $resolvedMacros = $sharedMacros;
        foreach ($macros as $macro) {
            $resolvedMacros[\sprintf(self::PATH_FORMAT_YAY, $macro)] = $macro;
        }
        
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
        foreach (\glob(self::GLOB_DIST) as $file) {
            $this->logger->debug('Pretty-printing '.\realpath($file));
            $code = \file_get_contents($file);
            $ast = $this->parser->parse($code);
            $code = $this->prettyPrinter->prettyPrintFile($ast);
            \file_put_contents($file, $code);
        }
    }
    
    private function lintFiles(): void
    {
        $this->logger->info('Linting');
        (new Process(self::PROCESS_PHP_CS_FIXER))->run();
    }
}