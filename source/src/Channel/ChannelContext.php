<?php

declare(strict_types=1);

namespace App\Channel;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ChannelContext implements ChannelContextInterface
{
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly RequestStack $requestStack,
    )
    {
    }

    public function getChannel(): ChannelInterface
    {
        $codeFromUrl = $this->requestStack->getMainRequest()?->query?->get('_channel_code');
        $codeFromSession = $this->requestStack->getSession()->get('_channel_code');

        if ($codeFromUrl === null && $codeFromSession === null) {
            return $this->channelRepository->findAll()[0];
        }

        if ($codeFromUrl) {
            $this->requestStack->getSession()->set('_channel_code', $codeFromUrl);
            return $this->channelRepository->findOneByCode($codeFromUrl);
        }

        $codeFromSession = $this->requestStack->getSession()->get('_channel_code');
        if ($codeFromSession) {
            return $this->channelRepository->findOneByCode($codeFromSession);
        }

        return $this->channelRepository->findAll()[0];
    }
}
