<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Ui\Cli;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ubirak\Component\EventBus\Domain\EventListener;
use Ubirak\Component\EventBus\Domain\EventListenerHealthCheck;

class EventBusListenerCli extends Command
{
    private $eventListenersContainer;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->eventListenersContainer = $container;
    }

    protected function configure()
    {
        $this
            ->setName('eventbus:listen')
            ->setDefinition([
                new InputArgument('handlerId', InputArgument::REQUIRED, 'Run the listener that embed this handler'),
                new InputOption('health-path', null, InputOption::VALUE_OPTIONAL, 'Path to dump health stats'),
            ])
            ->setDescription('Run event listener specified')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceId = $input->getArgument('handlerId');

        if (false === $this->eventListenersContainer->has($serviceId)) {
            throw new \RuntimeException("No handler defined with id $serviceId found");
        }

        $eventListener = $this->eventListenersContainer->get($serviceId);

        if (false === $eventListener instanceof EventListener) {
            throw new \RuntimeException(sprintf('Listener should be a %s class', EventListener::class));
        }

        (new EventListenerHealthCheck($input->getOption('health-path') ?? 'php://stdout'))
            ->listenAndCheck($eventListener)
        ;
    }
}
