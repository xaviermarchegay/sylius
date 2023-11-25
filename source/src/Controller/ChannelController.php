<?php

declare(strict_types=1);

namespace App\Controller;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ChannelController extends AbstractController
{
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly CacheInterface $cache,
    )
    {
    }

    public function listChannels(): Response
    {
        $channels = $this->cache->get('channels_list', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return $this->channelRepository->findBy(criteria: ['enabled' => true], orderBy: ['name' => 'asc']);
        });

        return $this->render('channel/list.html.twig', [
            'channels' => $channels,
        ]);
    }
}
