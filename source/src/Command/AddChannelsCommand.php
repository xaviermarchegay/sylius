<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Currency\Currency;
use App\Entity\Taxonomy\Taxon;
use Sylius\Component\Channel\Factory\ChannelFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sylius:add-channels', description: 'Add Channels',)]
class AddChannelsCommand extends Command
{
    private const DATA = 'https://raw.githubusercontent.com/high54/Communes-France-JSON/master/france.json';
    private const NB = 100;

    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ChannelFactoryInterface    $channelFactory,
        private readonly RepositoryInterface        $localeRepository,
        private readonly RepositoryInterface        $currencyRepository,
        private readonly TaxonRepositoryInterface   $taxonRepository,
        string                                      $name = null
    )
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(name: 'nb',mode: InputArgument::OPTIONAL, description: 'Nb of channels to create', default: self::NB)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var LocaleInterface $locale */
        $locale = $this->localeRepository->findOneBy(['code' => 'en_US']);

        /** @var Currency $currency */
        $currency = $this->currencyRepository->findOneBy(['code' => 'USD']);

        /** @var Taxon $tawon */
        $taxon = $this->taxonRepository->findOneBy(['id' => 1]);

        if (!file_exists('/tmp/france.json')) {
            file_put_contents('/tmp/france.json', file_get_contents(self::DATA));
        }

        $allCities = json_decode(file_get_contents('/tmp/france.json'), true);
        shuffle($allCities);

        $count = 0;

        foreach ($allCities as $city) {

            if ($this->channelRepository->findOneByCode((string)$city['Code_commune_INSEE'])) {
                $output->writeln($city['Nom_commune'] . ' already exists');
                continue;
            }

            $output->writeln('channel ' . $city['Nom_commune']);

            /** @var ChannelInterface $channel */
            $channel = $this->channelFactory->createNamed($city['Nom_commune']);
            $channel->setCode((string)$city['Code_commune_INSEE']);
            $channel->setEnabled(true);
            $channel->setTaxCalculationStrategy('order_items_based');
            $channel->setDefaultLocale($locale);
            $channel->addLocale($locale);
            $channel->setHostname('sylius.piconerd.com');
            $channel->setBaseCurrency($currency);
            $channel->setMenuTaxon($taxon);

            $this->channelRepository->add($channel);
            $count++;

            if ($count === (int) $input->getArgument('nb')) {
                break;
            }
        }

        return Command::SUCCESS;
    }
}
