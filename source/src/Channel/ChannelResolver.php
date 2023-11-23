<?php

declare(strict_types=1);

namespace App\Channel;

use Sylius\Component\Channel\Context\RequestBased\RequestResolverInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

class ChannelResolver implements RequestResolverInterface
{
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
    )
    {
    }

    public function findChannel(Request $request): ?ChannelInterface
    {
        $codeFromUrl = $request->query?->get('_channel_code');
        $codeFromSession = $request->getSession()->get('_channel_code');

        if ($codeFromUrl === null && $codeFromSession === null) {
            return $this->channelRepository->findAll()[0];
        }

        if ($codeFromUrl) {
            $request->getSession()->set('_channel_code', $codeFromUrl);
            return $this->channelRepository->findOneByCode($codeFromUrl);
        }

        $codeFromSession = $request->getSession()->get('_channel_code');
        if ($codeFromSession) {
            return $this->channelRepository->findOneByCode($codeFromSession);
        }

        return $this->channelRepository->findAll()[0];
    }
}
