<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use Marwa\Framework\Contracts\CacheInterface;

final class SettingsStore
{
    private const CACHE_KEY = 'settings.module.values';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly SettingsCatalog $catalog,
        private readonly SettingsRepository $repository,
        private readonly SettingsApplier $applier,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        /** @var array<string, array<string, mixed>> $values */
        $values = $this->cache->remember(self::CACHE_KEY, null, function (): array {
            return $this->catalog->hydrate($this->repository->all());
        });

        return $values;
    }

    /**
     * @param array<string, array<string, mixed>> $values
     */
    public function update(array $values): void
    {
        $this->repository->save($this->catalog->flattenForStorage($values));
        $this->refresh();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function refresh(): array
    {
        $this->cache->forget(self::CACHE_KEY);
        $values = $this->all();
        $this->applier->apply($values);

        return $values;
    }
}
