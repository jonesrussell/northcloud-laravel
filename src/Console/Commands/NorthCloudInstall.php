<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class NorthCloudInstall extends Command
{
    protected $signature = 'northcloud:install
                            {--force : Overwrite existing published files}
                            {--skip-ui : Skip UI component publishing}';

    protected $description = 'Install NorthCloud assets, views, and UI components';

    /** @var list<string> */
    protected array $uiComponents = [
        'badge',
        'button',
        'card',
        'checkbox',
        'dialog',
        'input',
        'label',
        'select',
    ];

    public function handle(): int
    {
        info('Installing NorthCloud...');

        $force = $this->option('force');
        $params = $force ? ['--force' => true] : [];

        $this->publishTag('northcloud-config', 'Configuration', $params);
        $this->publishTag('northcloud-migrations', 'Migrations', $params);
        $this->publishTag('northcloud-admin-migrations', 'Admin migrations', $params);
        $this->publishTag('northcloud-admin-views', 'Admin views', $params);
        $this->publishTag('northcloud-admin-components', 'Admin components', $params);
        $this->publishTag('northcloud-user-views', 'User views', $params);
        $this->publishTag('northcloud-user-components', 'User components', $params);
        $this->publishTag('northcloud-admin-layout', 'Admin layout', $params);

        if ($this->option('skip-ui')) {
            note('Skipping UI component publishing (--skip-ui).');
        } else {
            $this->publishTag('northcloud-ui-components', 'UI components', $params);
            $this->verifyUiComponents();
        }

        info('NorthCloud installed successfully.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function publishTag(string $tag, string $label, array $params = []): void
    {
        $this->callSilently('vendor:publish', array_merge(
            ['--tag' => $tag],
            $params,
        ));

        note("Published: {$label}");
    }

    protected function verifyUiComponents(): void
    {
        $missing = [];

        foreach ($this->uiComponents as $component) {
            $indexPath = resource_path("js/components/ui/{$component}/index.ts");

            if (! file_exists($indexPath)) {
                $missing[] = $component;
            }
        }

        if ($missing !== []) {
            warning('Missing UI components: '.implode(', ', $missing));
        }
    }
}
