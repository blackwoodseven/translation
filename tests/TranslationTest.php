<?php
namespace BlackwoodSeven\Test;

class TranslationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        parent::setUp();

        $this->app = new \Silex\Application(['debug' => true]);

        $this->app->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.path' => __DIR__ . '/twig',
        ));

        $this->app->register(new \Silex\Provider\TranslationServiceProvider(), [
            'locale_fallbacks' => ['en'],
            'locale' => 'en',
        ]);

        $this->app->register(new \BlackwoodSeven\Translation\TranslationServiceProvider(), [
            'translation.path' => __DIR__ . '/locale',
            'translation.contexts' => ['test'],
            'translation.locales' => ['test'],
        ]);

    }

    public function testLocaleInCode()
    {
        $this->app['translator']->setLocale('test');
        $text = $this->app['translator']->trans('This should be translated');
        $this->assertEquals('This is now translated', $text);
        $text = $this->app['translator']->trans('This should not be translated');
        $this->assertEquals('This should not be translated', $text);

        $date = new \DateTime('2016-09-08 22:29');
        $text = $this->app['formatter.date']($date, 'medium_date');
        $this->assertEquals('8 Sep - localized 2016', $text);

        $number = 1234.56;
        $text = $this->app['formatter.number']($number, 4);
        $this->assertEquals('1&234#5600', $text);

        $number = 1234.5612;
        $text = $this->app['formatter.number']($number, 2);
        $this->assertEquals('1&234#56', $text);

        $number = 1234.5678;
        $text = $this->app['formatter.number']($number, 2);
        $this->assertEquals('1&234#57', $text);

        $number = 7890.1234;
        $text = $this->app['formatter.price']($number);
        $this->assertEquals('7&890#12', $text);

        $number = 7890.1299;
        $text = $this->app['formatter.price']($number);
        $this->assertEquals('7&890#13', $text);
    }

    public function testLocaleInTemplates()
    {
        $approval = [
            'datetime' => new \DateTime('2016-09-08 22:29'),
            'number' => 1234.56,
            'price' => 7890.1299,
        ];
        $this->assertEquals('en', $this->app['locale']);
        $this->app['translator']->setLocale('test');
        $this->assertEquals('test', $this->app['locale']);
        $html = $this->app['twig']->render('test.twig', $approval);
        $this->assertContains('This is now translated', $html);
        $this->assertContains('This should not be translated', $html);
        $this->assertContains('Time is: 8 Sep - localized 2016', $html);
        $this->assertContains('Number is: 1&234#5600', $html);
        $this->assertContains('Price is: 7&890#13', $html);
    }
}
