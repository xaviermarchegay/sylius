<?php

declare(strict_types=1);

namespace App\Channel;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ChannelContext implements ChannelContextInterface
{
    private const CHANNEL_CODE = '_channel_code';

    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly RequestStack               $requestStack,
    )
    {
    }

    public function getChannel(): ChannelInterface
    {
        $codeFromUrl = $this->requestStack->getMainRequest()?->query?->get(self::CHANNEL_CODE);
        $codeFromSession = $this->requestStack->getSession()->get(self::CHANNEL_CODE);

        if ($codeFromUrl === null && $codeFromSession === null) {
            /** @var ChannelInterface $firstChannel */
            $firstChannel = $this->channelRepository->findOneBy(criteria: ['enabled' => true]);
            $this->requestStack->getSession()->set(self::CHANNEL_CODE, $firstChannel->getCode());
            return $firstChannel;
        }

        if ($codeFromUrl) {
            $this->requestStack->getSession()->set(self::CHANNEL_CODE, $codeFromUrl);
            return $this->channelRepository->findOneByCode($codeFromUrl);
        }

        $codeFromSession = $this->requestStack->getSession()->get(self::CHANNEL_CODE);
        if ($codeFromSession) {
            return $this->channelRepository->findOneByCode($codeFromSession);
        }

        /** @var ChannelInterface $firstChannel */
        $firstChannel = $this->channelRepository->findOneBy(criteria: ['enabled' => true]);
        $this->requestStack->getSession()->set(self::CHANNEL_CODE, $firstChannel->getCode());
        return $firstChannel;
    }
}
