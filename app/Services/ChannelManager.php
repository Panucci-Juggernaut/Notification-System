<?php

namespace App\Services;

use App\Contracts\NotificationChannelInterface;
use InvalidArgumentException;

class ChannelManager
{
    /** @var array<string, NotificationChannelInterface> */
    protected array $channels = [];

    /** Register a notification channel with a unique slug identifier. */
    public function register(string $slug, NotificationChannelInterface $channel): void
    {
        $this->channels[$slug] = $channel;
    }

    /** Retrieve a registered channel by its slug. */
    public function get(string $slug): NotificationChannelInterface
    {
        if (!isset($this->channels[$slug])) {
            throw new InvalidArgumentException("Notification channel [{$slug}] is not registered.");
        }

        return $this->channels[$slug];
    }

    /** Return all registered channels. */
    public function all(): array
    {
        return $this->channels;
    }

    /** Check if a channel slug is registered. */
    public function has(string $slug): bool
    {
        return isset($this->channels[$slug]);
    }

    /** Return channels that support a given event type. */
    public function getChannelsForEvent(string $eventType): array
    {
        return array_filter($this->channels, function (NotificationChannelInterface $channel) use ($eventType) {
            return $channel->supports($eventType);
        });
    }
}
