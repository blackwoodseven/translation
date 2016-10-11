<?php

namespace BlackwoodSeven\Translation;

use Pimple\Container;
use Silex\Translator;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Loader\PoFileLoader;

class TranslationServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $app)
    {
        // Set default options.
        $app['translation.path'] = [];
        $app['translation.contexts'] = [];
        $app['translation.locales'] = [];
        $app['translation.locales_exclude'] = ['test'];

        $app['translation.context.date_format'] = 'date_format';
        $app['translation.context.number_format'] = 'number_format';
        $app['encoding'] = $app['encoding'] ?? 'UTF-8';

        // Auto-discovery of .po files.
        $app['translator'] = $app->extend('translator', function($translator, $app) {
            $translator->addLoader('po', new PoFileLoader());
            $paths = $app['translation.path'];
            if (!is_array($paths)) {
                $paths = $paths ? [$paths] : [];
            }
            foreach ($paths as $path) {
                $path .= '/*';
                foreach (glob($path) as $file) {
                    $locale = basename($file);
                    if ($app['translation.locales'] && !in_array($locale, $app['translation.locales'])) {
                        continue;
                    }
                    elseif (!$app['translation.locales'] && $app['translation.locales_exclude'] && in_array($locale, $app['translation.locales_exclude'])) {
                        continue;
                    }
                    foreach ($app['translation.contexts'] as $context) {
                        $poFile = $file . '/' . $context . '.po';
                        if (!file_exists($poFile)) {
                            continue;
                        }
                        $translator->addResource('po', $poFile, $locale);
                    }
                }
            }
            return $translator;
        });

        $this->registerFormatters($app);
        $this->registerTwigFilters($app);
    }

    public function registerFormatters($app)
    {
        $app['formatter.date'] = $app->protect(function ($date, $formatType = null, $timezone = null) use ($app) {
            /**
             * Converts a date according to a format.
             */
            $convert = function ($date, $format) use ($app, $timezone) {
                $filters = $app['twig']->getFilters();
                $callable = $filters['date']->getCallable();
                return $callable($app['twig'], $date, $format, $timezone);
            };

            /**
             * Translates names of months and days of week.
             */
            $map = function ($symbol) use ($app, $convert, $date) {
                $pattern = '/[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]/';
                if (preg_match($pattern, $symbol)) {
                    $value = $convert($date, $symbol);
                    if (preg_match('/[DlMF]/', $symbol)) {
                        $value = $app['translator']->trans($value);
                    }
                } else {
                    $value = $symbol;
                }

                return $value;
            };

            $format = isset($formatType) ? $app['translator']->trans($app['translation.context.date_format'] . '.' . $formatType) : null;
            if (!isset($format) || $format === $formatType) {
                $result = $convert($date, null);
            } else {
                if (!preg_match('/[DlMF]/', $format) || !isset($app['translator'])) {
                    $result = $convert($date, $format);
                } else {
                    for ($i = 0; $i < mb_strlen($format, $app['encoding']); $i++) {
                        $symbols[] = mb_substr($format, $i, 1, $app['encoding']);
                    }
                    $result = implode(array_map($map, $symbols));
                }
            }

            return $result;
        });

        $app['formatter.number'] = $app->protect(function ($v, $decimals = 0) use ($app) {
            if (!is_numeric($v)) {
                return $v;
            }

            $dec_point = $app['translator']->trans($app['translation.context.number_format'] . '.dec_point');
            $thousands_sep = $app['translator']->trans($app['translation.context.number_format'] . '.thousands_sep');
            $formatted = number_format($v, abs($decimals), $dec_point, $thousands_sep);
            // Negative $decimals means *maximum* number of decimals.
            if ($decimals < 0) {
                $formatted = rtrim(rtrim($formatted, '0'), $dec_point);
            }
            return $formatted;
        });

        $app['formatter.price'] = $app->protect(function ($v) use ($app) {
            return is_float($v) ? $app['formatter.number']($v, 2) : $v;
        });

    }

    protected function registerTwigFilters($app)
    {
        /**
         * Extends Twig with date translation filter.
         */
        $app['twig'] = $app->extend('twig', function (\Twig_Environment $twig) use ($app) {
            /**
             * Adds the `formatDate` filter.
             *
             * Use:
             * <div>
             *     {{ entity.date|formatDate }}
             * </div>
             *
             * or:
             * <div>
             *     {{ entity.date|formatDate('short_date') }}
             * </div>
             *
             * or:
             * <div>
             *    {{ entity.date|formatDate('short_date', 'Europe/London') }}
             * </div>
             */
            $twig->addFilter(
                new \Twig_SimpleFilter(
                    'formatDate',
                    $app['formatter.date']
                )
            );

            $twig->addFilter(
                new \Twig_SimpleFilter(
                    'formatNumber',
                    $app['formatter.number']
                )
            );

            $twig->addFilter(
                new \Twig_SimpleFilter(
                    'formatPrice',
                    $app['formatter.price']
                )
            );

            return $twig;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
