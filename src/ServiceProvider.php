<?php

namespace Lwekuiper\StatamicActivecampaign;

use Statamic\Statamic;
use Statamic\Events\SubmissionCreated;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Form;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;
use Lwekuiper\StatamicActivecampaign\Connectors\ActiveCampaignConnector;
use Lwekuiper\StatamicActivecampaign\Fieldtypes\ActivecampaignList;
use Lwekuiper\StatamicActivecampaign\Fieldtypes\ActivecampaignMergeFields;
use Lwekuiper\StatamicActivecampaign\Fieldtypes\ActivecampaignTag;
use Lwekuiper\StatamicActivecampaign\Fieldtypes\StatamicFormFields;
use Lwekuiper\StatamicActivecampaign\Listeners\AddFromSubmission;
use Lwekuiper\StatamicActivecampaign\Stache\FormConfigRepository;
use Lwekuiper\StatamicActivecampaign\Stache\FormConfigStore;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        ActivecampaignList::class,
        ActivecampaignMergeFields::class,
        ActivecampaignTag::class,
        StatamicFormFields::class,
    ];

    protected $listen = [
        SubmissionCreated::class => [AddFromSubmission::class],
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function register()
    {
        $this->app->singleton(FormConfigRepository::class, function () {
            return new FormConfigRepository($this->app['stache']);
        });

        $this->app->singleton(ActiveCampaignConnector::class, function () {
            return new ActiveCampaignConnector();
        });

        $this->publishes([
            __DIR__.'/../config/activecampaign.php' => config_path('statamic/activecampaign.php'),
        ], 'statamic-activecampaign-config');
    }

    public function bootAddon()
    {
        Nav::extend(function ($nav) {
            $nav->create('ActiveCampaign')
                ->section('Tools')
                ->route('activecampaign.index')
                ->can('index', Form::class)
                ->icon('<svg viewBox="0 0 124 124" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="m99.5 61.8-64.8 43c-3 2-4.5 5.2-4.5 8.5V124l78.5-51.5c3.5-2.5 5.8-6.5 5.8-10.7s-2-8.3-5.8-10.8L30.2 0v10c0 3.5 1.8 6.8 4.5 8.5l64.8 43.3Z"/><path fill="currentColor" d="M60.6 65.2c3.5 2.2 8 2.2 11.4 0l5.5-3.7-40.8-27.6c-2.5-1.7-6.2 0-6.2 3.2v8.2l21.1 14.2 8.9 5.7Z"/></svg>')
                ->children(function () {
                    return Form::all()->sortBy->title()->map(function ($form) {
                        return Nav::item($form->title())
                            ->url(cp_route('activecampaign.edit', $form->handle()))
                            ->can('edit', $form);
                    });
                });
        });

        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', [
                '--tag' => 'statamic-activecampaign-config',
            ]);
        });

        $formConfigStore = new FormConfigStore();
        $formConfigStore->directory(base_path('resources/activecampaign'));
        app(Stache::class)->registerStore($formConfigStore);
    }
}
