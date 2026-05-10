<?php

namespace App\Providers;

use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;

class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $directoriesToTransform = [
            app_path('Domain'),
        ];

        if (is_dir(app_path('Data'))) {
            $directoriesToTransform[] = app_path('Data');
        }

        $config
            ->outputDirectory(base_path('frontend/src/types'))
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(...$directoriesToTransform)
            ->writer(new FlatModuleWriter('generated.ts'));
    }
}
