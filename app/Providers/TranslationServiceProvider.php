<?php

namespace App\Providers;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Section as FormSection;
use Filament\Navigation\NavigationGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Field::macro('localizeLabel', function (string | Htmlable | Closure | null $customLabel = null): static {
            /** @var Field $this */
            return TranslationServiceProvider::localizeLabelGeneric($this, $customLabel);
        });

        Column::macro('localizeLabel', function (string | Htmlable | Closure | null $customLabel = null): static {
            /** @var Column $this */
            return TranslationServiceProvider::localizeLabelGeneric($this, $customLabel);
        });

        Field::configureUsing(static function (Field $field): void {
            TranslationServiceProvider::localizeLabelGeneric($field);
        }, isImportant: true);

        Column::configureUsing(static function (Column $column): void {
            TranslationServiceProvider::localizeLabelGeneric($column);
        }, isImportant: true);

        FormSection::configureUsing(static function (FormSection $section): void {
            TranslationServiceProvider::localizeSectionGeneric($section);
        }, isImportant: true);

        InfolistSection::configureUsing(static function (InfolistSection $section): void {
            TranslationServiceProvider::localizeSectionGeneric($section);
        }, isImportant: true);

        Action::configureUsing(static function (Action $action): void {
            TranslationServiceProvider::localizeLabelGeneric($action);
        }, isImportant: true);

        FormAction::configureUsing(static function (FormAction $action): void {
            TranslationServiceProvider::localizeLabelGeneric($action);
        }, isImportant: true);

        TableAction::configureUsing(static function (TableAction $action): void {
            try {
                TranslationServiceProvider::localizeLabelGeneric($action);
            } catch (\Exception $e) {
                // Silently skip if action doesn't belong to a table context
                if (str_contains($e->getMessage(), 'does not belong to a table')) {
                    return;
                }
                throw $e;
            }
        }, isImportant: true);

        BulkAction::configureUsing(static function (BulkAction $action): void {
            TranslationServiceProvider::localizeLabelGeneric($action);
        }, isImportant: true);

        ActionGroup::configureUsing(static function (ActionGroup $actionGroup): void {
            TranslationServiceProvider::localizeLabelGeneric($actionGroup);
        }, isImportant: true);

        NavigationGroup::macro('localizeLabel', function () {
            /** @var NavigationGroup $this */
            $label = $this->getLabel();

            if (filled($label)) {
                $translatedLabel = translate($label);
                $this->label(ucfirst($translatedLabel));
            }

            return $this;
        });

        Tab::macro('localizeLabel', function () {
            /** @var Tab $this */
            $label = $this->getLabel();

            if (filled($label)) {
                $translatedLabel = translate($label);
                $this->label(ucfirst($translatedLabel));
            }

            return $this;
        });

        NavigationGroup::configureUsing(static function (NavigationGroup $navigationGroup): void {
            TranslationServiceProvider::localizeLabelGeneric($navigationGroup);
        }, isImportant: true);

        Tab::configureUsing(static function (Tab $tab): void {
            TranslationServiceProvider::localizeLabelGeneric($tab);
        }, isImportant: true);
    }

    public static function localizeLabelGeneric($object, string | Htmlable | Closure | null $customLabel = null)
    {
        $label = filled($customLabel) ? $customLabel : static::processedLabel($object->getLabel());

        $object->label(translate($label));

        return $object;
    }

    public static function localizeHeadingGeneric($object): void
    {
        if (! method_exists($object, 'getHeading') || ! method_exists($object, 'heading')) {
            return;
        }

        $heading = $object->getHeading();

        if (filled($heading)) {
            $object->heading(translate((string) $heading));
        }
    }

    public static function localizeSectionGeneric($object): void
    {
        if (method_exists($object, 'getHeading') && method_exists($object, 'heading')) {
            $heading = $object->getHeading();

            if (filled($heading)) {
                $object->heading(translate((string) $heading));
            }
        }

        if (method_exists($object, 'getDescription') && method_exists($object, 'description')) {
            $description = $object->getDescription();

            if (filled($description)) {
                $object->description(translate((string) $description));
            }
        }
    }

    public static function processedLabel(Htmlable | null | string $label): string
    {
        if (str_ends_with($label, ' id')) {
            $label = str_replace(' id', '', $label);
        }

        return ucfirst($label);
    }
}
