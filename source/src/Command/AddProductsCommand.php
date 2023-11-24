<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Channel\ChannelPricing;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\s;

#[AsCommand(name: 'sylius:add-products', description: 'Init',)]
class AddProductsCommand extends Command
{
    private const NB = 100;
    private const CHUNK = 25;

    public function __construct(
        private readonly ChannelRepositoryInterface        $channelRepository,
        private readonly ProductRepositoryInterface        $productRepository,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
        private readonly ProductFactoryInterface           $productFactory,
        private readonly ProductVariantFactoryInterface    $productVariantFactory,
        private readonly FactoryInterface                  $productTaxonFactory,
        private readonly FactoryInterface                  $channelPricingFactory,
        private readonly TaxonRepositoryInterface          $taxonRepository,
        private readonly EntityManagerInterface            $manager,
        string                                             $name = null
    )
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(name: 'nb', mode: InputArgument::OPTIONAL, description: 'Nb of products to create', default: self::NB);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nb = (int)$input->getArgument('nb');

        $chunks = array_chunk(range(1, $nb), self::CHUNK);
        foreach ($chunks as $i => $chunk) {
            $output->writeln('chunk ' . $i + 1 . ' out of ' . count($chunks));
            $this->createProducts($output);
            $this->manager->clear();
        }

        return Command::SUCCESS;
    }

    private function createProducts(OutputInterface $output): void
    {
        $channels = $this->channelRepository->findAll();
        $taxons = $this->taxonRepository->findAll();

        $faker = Factory::create();
        $slugger = new AsciiSlugger();
        foreach (range(1, self::CHUNK) as $i) {

            $name = $faker->sentence();
            $code = 'code_' . random_int(1, PHP_INT_MAX);

            /** @var ProductInterface $product */
            $product = $this->productFactory->createNew();
            $product->setName(s($name)->title()->toString());
            $product->setCode($code);
            $product->setSlug($slugger->slug($product->getName())->toString());
            $product->setDescription($faker->paragraphs(3, true));
            $this->productRepository->add($product);

            $variantProduct = $this->productVariantFactory->createForProduct($product);
            $variantProduct->setCode($code);
            $variantProduct->setName($code);
            $variantProduct->setShippingRequired(true);

            $this->productVariantRepository->add($variantProduct);

            foreach ($channels as $channel) {
                if ((bool)random_int(0, 1) === true) {
                    continue;
                }

                /** @var ChannelInterface $channel */
                $product->addChannel($channel);

                /** @var ChannelPricing $channelPricing */
                $channelPricing = $this->channelPricingFactory->createNew();
                $channelPricing->setChannelCode($channel->getCode());
                $channelPricing->setPrice(random_int(10000, 50000));

                $variantProduct->addChannelPricing($channelPricing);
            }

            shuffle($taxons);
            $product->setMainTaxon($taxons[0]);

            foreach (array_slice($taxons, 0, 3) as $taxon) {
                /** @var ProductTaxonInterface $productTaxon */
                $productTaxon = $this->productTaxonFactory->createNew();
                $productTaxon->setTaxon($taxon);
                $productTaxon->setProduct($product);

                $product->addProductTaxon($productTaxon);
            }

            $output->writeln(' product ' . $product->getCode() . ' ' . $product->getName());
        }
    }
}
