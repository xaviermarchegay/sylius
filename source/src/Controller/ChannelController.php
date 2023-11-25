<?php

declare(strict_types=1);

namespace App\Controller;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ChannelController extends AbstractController
{
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
    )
    {
    }

    public function listChannels(): Response
    {
        return $this->render('channel/list.html.twig', [
            'channels' => $this->channelRepository->findBy(criteria: [], orderBy: ['name' => 'asc']),
        ]);
    }
}
