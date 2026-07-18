<?php

namespace Codedge\Updater\Events;

use Codedge\Updater\Models\Release;
use Illuminate\Support\Facades\Artisan;

class UpdateSucceeded
{
    /**
     * @var Release
     */
    protected $release;

    public function __construct(Release $release)
    {
        $this->release = $release;

        Artisan::call('updater:after');
    }

    /**
     * Get the new version.
     *
     * @return string
     */
    public function getVersionUpdatedTo(): ?string
    {
        return $this->release->getVersion();
    }
}
