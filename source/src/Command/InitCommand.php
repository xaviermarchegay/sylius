<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Channel\ChannelPricing;
use App\Entity\Currency\Currency;
use App\Entity\Taxonomy\Taxon;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Factory\ChannelFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sylius:init', description: 'Init', )]
class InitCommand extends Command
{
    private const DATA = 'https://raw.githubusercontent.com/high54/Communes-France-JSON/master/france.json';
    private const CHANNELS_NB = 500;

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ChannelFactoryInterface $channelFactory,
        private readonly FactoryInterface $channelPricingFactory,
        private readonly RepositoryInterface $localeRepository,
        private readonly RepositoryInterface $currencyRepository,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly EntityManagerInterface $manager,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->clean();
        $this->createChannels($output);

        $channels = $this->channelRepository->findAll();
        $products = $this->productRepository->findAll();

        foreach ($products as $product) {
            /** @var ProductInterface $product */
            foreach ($channels as $channel) {
                /** @var ChannelInterface $channel */
                $product->addChannel($channel);

                foreach ($product->getEnabledVariants() as $variant) {
                    if (null === $channelPricing = $variant->getChannelPricingForChannel($channel)) {
                        /** @var ChannelPricing $channelPricing */
                        $channelPricing = $this->channelPricingFactory->createNew();
                        $channelPricing->setChannelCode($channel->getCode());
                        $channelPricing->setPrice(random_int(0, 500));

                        $variant->addChannelPricing($channelPricing);
                    }

                }
            }
            $this->manager->flush();
        }

        return Command::SUCCESS;
    }

    private function createChannels(OutputInterface $output): void
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

            $output->writeln($city['Nom_commune'] . ' created');

            /** @var ChannelInterface $channel */
            $channel = $this->channelFactory->createNamed($city['Nom_commune']);
            $channel->setCode((string)$city['Code_commune_INSEE']);
            $channel->setEnabled(true);
            $channel->setTaxCalculationStrategy('order_items_based');
            $channel->setDefaultLocale($locale);
            $channel->addLocale($locale);
            $channel->setBaseCurrency($currency);
            $channel->setMenuTaxon($taxon);

            $this->channelRepository->add($channel);
            $count++;

            if ($count === self::CHANNELS_NB) {
                break;
            }
        }
    }

    private function clean(): void
    {
        foreach ($this->orderRepository->findAll() as $order) {
            $this->manager->remove($order);
        }

        foreach ($this->channelRepository->findAll() as $channel) {
            if ($channel->getCode() === 'FASHION_WEB') {
                continue;
            }
            $this->manager->remove($channel);
        }
    }
}
