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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Vectory\Services\VectorDefinitionGeneratorInterface;

final class CalculateCommand extends Command
{
    private /* VectorDefinitionGeneratorInterface */ $vectorDefinitionGenerator;
    private /* LoggerInterface */ $logger;

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
            ->setName('calculate')
            ->setDescription('Calculate memory usage of each data structure');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->vectorDefinitionGenerator->generate() as $vectorDefinition) {
            /** @var Vectory\ValueObjects\VectorDefinitionInterface $vectorDefinition */
            $dataStructures = [
                'array',
                \Ds\Deque::class,
                \Ds\Vector::class,
                \SplFixedArray::class,
                $vectorDefinition->getClassName(),
            ];
            foreach ($dataStructures as $dataStructureId) {
                $command = [
                    __DIR__.'/../../bin/calculate',
                    $dataStructureId,
                    \json_encode($vectorDefinition->getDefaultValue()),
                    1,
                ];
                $process = new Process($command);
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException(\trim($process->getErrorOutput()));
                }
                if (0 === \strpos($dataStructureId, 'Ds\\')) {
                    $dataStructureId .= \extension_loaded('ds') ? ' (extension)' : ' (polyfill)';
                }
                $output->writeln(\sprintf('%6.3F %s', \trim($process->getOutput()), $dataStructureId));
            }
            $output->writeln('');
        }
    }
}
