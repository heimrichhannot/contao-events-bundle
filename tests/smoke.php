<?php
require dirname(__DIR__).'/vendor/autoload.php';
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
$project = sys_get_temp_dir().'/contao-events-smoke-'.bin2hex(random_bytes(6));
mkdir($project.'/config', 0777, true);
file_put_contents($project.'/composer.json', '{}');
file_put_contents($project.'/config/config.yaml', "framework:\n  secret: events-test\ndoctrine:\n  dbal:\n    url: mysql://test:test@localhost/test?serverVersion=10.11.0-MariaDB\n");
register_shutdown_function(static function () use ($project) { (new \Symfony\Component\Filesystem\Filesystem())->remove($project); });
ContaoKernel::setProjectDir($project);
$kernel = new ContaoKernel('test', true);
$plugins = [];
$installed = json_decode(file_get_contents(dirname(__DIR__).'/vendor/composer/installed.json'), true);
foreach ($installed['packages'] as $package) {
    if (isset($package['extra']['contao-manager-plugin'])) {
        $class = $package['extra']['contao-manager-plugin'];
        $plugins[$package['name']] = new $class();
    }
}
$plugins['heimrichhannot/contao-events-bundle'] = new \HeimrichHannot\EventsBundle\ContaoManager\Plugin();
$kernel->setPluginLoader(new \Contao\ManagerPlugin\PluginLoader(null, $plugins));
$kernel->boot();
echo "Kernel booted\n";
$container = $kernel->getContainer();
$container->get('contao.framework')->initialize();
echo "Framework initialized\n";
foreach (['tl_calendar','tl_calendar_events','tl_settings'] as $table) {
    Contao\Controller::loadDataContainer($table);
    echo "$table loaded\n";
}
Contao\Config::set('subEventMode', 'entity');
$manager = $container->get(HeimrichHannot\EventsBundle\Manager\EventsManager::class);
$manager->initCalendarSubEventsConfig();
$manager->initCalendarSubEventsDca();
echo "Sub-event DCA loaded\n";
if ('tl_calendar_events' !== $GLOBALS['TL_DCA']['tl_calendar_sub_events']['config']['ptable']
    || !($GLOBALS['TL_DCA']['tl_calendar_sub_events']['fields']['published']['toggle'] ?? false)
    || isset($GLOBALS['TL_DCA']['tl_calendar_sub_events']['config']['oncut_callback'])) {
    throw new RuntimeException('Invalid sub-event DCA configuration.');
}
foreach (['tl_calendar_events','tl_calendar_sub_events'] as $table) {
    foreach (['config','fields','list'] as $section) {
        $check = function ($data, $path) use (&$check) {
            foreach ($data as $key=>$value) {
                if (is_array($value) && isset($value[0],$value[1]) && is_string($value[0]) && is_string($value[1]) && (str_contains($path, 'callback') || str_contains((string)$key,'callback'))) {
                    if ($container = Contao\System::getContainer()) {
                        $obj = Contao\System::importStatic($value[0]);
                        if (!method_exists($obj, $value[1])) throw new RuntimeException("Missing callback $path/$key: ".implode('::',$value));
                    }
                } elseif (is_array($value)) $check($value, "$path/$key");
            }
        };
        $check($GLOBALS['TL_DCA'][$table][$section], "$table/$section");
    }
}
echo "All DCA callbacks resolve\n";

$twig = $container->get('twig');
foreach (['event_full.html.twig', 'event_teaser.html.twig', 'event_upcoming.html.twig', 'image.html.twig'] as $template) {
    $twig->load('@HeimrichHannotContaoEvents/'.$template);
}
echo "Twig templates compile\n";
Contao\Config::set('subEventMode', 'relation');
$manager->initCalendarEventsDcaForSubEvents();
if (!isset($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['parentEvent'])) {
    throw new RuntimeException('Missing parent-event field.');
}
echo "Relation DCA loaded\n";
$kernel->shutdown();
