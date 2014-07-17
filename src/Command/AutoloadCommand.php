<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Contao\ContaoBundle
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\ContaoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Exception;

/**
 * Generates the autoload.php files
 *
 * @author Leo Feyer <https://contao.org>
 */
class AutoloadCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('contao:autoload')
            ->setDefinition([
                new InputArgument('modules', InputArgument::OPTIONAL, 'An optional list of comma separated modules'),
                new InputOption('override', '-o', InputOption::VALUE_NONE, 'Override existing autoload.php files.'),

            ])
            ->setDescription('Generate the Contao autoload.php files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs      = new Filesystem();
        $modules = $input->getArgument('modules');
        $year    = date('Y');
        $root    = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        // Build the modules array
        if ($modules === null) {
            $dirs = Finder::create()->directories()->in($root . '/system/modules');

            foreach ($dirs as $dir) {
                $modules[] = $dir->getFilename();
            }
        } else {
            $modules = array_map('trim', explode(',', $modules));
        }

        foreach ($modules as $module) {
            if (!$fs->exists($root . '/system/modules/' . $module)) {
                throw new Exception('Invalid module name "' . $module . '"');
            }

            // The autoload.php file exists
            if (!$input->getOption('override') && $fs->exists($root . '/system/modules/' . $module . '/config/autoload.php')) {
                $output->writeln("  The autoload.php file of the $module module exists. Use the <info>--override</info> option to override it.");
                continue;
            }

            $classWidth  = 0;
            $classLoader = [];
            $namespaces  = [];

            // Default configuration
            $defaultConfig = [
                'register_namespaces' => true,
                'register_classes'    => true,
                'register_templates'  => true,
            ];

            // Create the autoload.ini file if it does not yet exist
            if (!$fs->exists($root . '/system/modules/' . $module . '/config/autoload.ini')) {
                $fs->dumpFile($root . '/system/modules/' . $module . '/config/autoload.ini',
<<<EOT
;;
; List modules which are required to be loaded beforehand
;;
requires[] = "core"

;;
; Register public folders
;;
public[] = "assets"

;;
; Configure what you want the autoload creator to register
;;
register_namespaces = true
register_classes    = true
register_templates  = true

;;
; Override the default configuration for certain sub directories
;;
[vendor/*]
register_namespaces = false
register_classes    = false
register_templates  = false

EOT
                );
            }

            // Merge the module configuration

            $defaultConfig = array_merge(
                $defaultConfig,
                parse_ini_file($root . '/system/modules/' . $module . '/config/autoload.ini', true)
            );

            $fileObjects = Finder::create()->files()->name('*.php')->in($root . '/system/modules/' . $module);

            // Add the files
            foreach ($fileObjects as $fileObject) {
                $relpath = $fileObject->getRelativePathname();

                if (strncmp($relpath, 'assets/', 7) === 0 || strncmp($relpath, 'config/', 7) === 0 || strncmp($relpath, 'dca/', 4) === 0 || strncmp($relpath, 'languages/', 10) === 0 || strncmp($relpath, 'templates/', 10) === 0) {
                    continue;
                }

                $config = $defaultConfig;

                // Search for a path configuration (see #4776)
                foreach ($defaultConfig as $pattern=>$pathConfig) {
                    if (is_array($pathConfig) && fnmatch($pattern, $relpath)) {
                        $config = array_merge($defaultConfig, $pathConfig);
                        break;
                    }
                }

                // Continue if neither namespaces nor classes shall be registered
                if (!$config['register_namespaces'] && !$config['register_classes']) {
                    continue;
                }

                $buffer  = '';
                $matches = [];

                // Store the file size for fread()
                $size = filesize($root . '/system/modules/' . $module . '/' . $relpath);
                $fh   = fopen($root . '/system/modules/' . $module . '/' . $relpath, 'rb');

                // Read until a class or interface definition has been found
                while (!preg_match('/(class|interface) ' . preg_quote(basename($relpath, '.php'), '/') . '/', $buffer, $matches) && $size > 0 && !feof($fh)) {
                    $length  = min(512, $size);
                    $buffer .= fread($fh, $length);
                    $size   -= $length; // see #4876
                }

                fclose($fh);

                // The file does not contain a class or interface
                if (empty($matches)) {
                    continue;
                }

                $namespace = preg_replace('/^.*namespace ([^; ]+);.*$/s', '$1', $buffer);

                // No namespace declaration found
                if ($namespace == $buffer) {
                    $namespace = '';
                }

                unset($buffer);

                // Register only the first chunk as namespace
                if ($namespace != '') {
                    if ($config['register_namespaces'] && $namespace != 'Contao') {
                        if (strpos($namespace, '\\') !== false) {
                            $namespaces[] = substr($namespace, 0, strpos($namespace, '\\'));
                        } else {
                            $namespaces[] = $namespace;
                        }
                    }

                    $namespace .=  '\\';
                }

                // Register the class
                if ($config['register_classes']) {
                    $key               = $namespace . basename($relpath, '.php');
                    $classLoader[$key] = 'system/modules/' . $module . '/' . $relpath;
                    $classWidth        = max(strlen($key), $classWidth);
                }
            }

            $tplWidth  = 0;
            $tplLoader = [];

            // Scan for templates
            if ($fs->exists($root . '/system/modules/' . $module . '/templates')) {
                $fileObjects = Finder::create()->files()->name('/.*(tpl|html5|xhtml)$/')->in($root . '/system/modules/' . $module . '/templates');

                // Add the files
                foreach ($fileObjects as $fileObject) {
                    $config  = $defaultConfig;
                    $relpath = 'templates/' . $fileObject->getRelativePathname();

                    // Search for a path configuration (see #4776)
                    foreach ($defaultConfig as $pattern=>$pathConfig) {
                        if (is_array($pathConfig) && fnmatch($pattern, $relpath)) {
                            $config = array_merge($defaultConfig, $pathConfig);
                            break;
                        }
                    }

                    // Continue if templates shall not be registered
                    if (!$config['register_templates']) {
                        continue;
                    }

                    $tplExts   = ['tpl', 'html5', 'xhtml'];
                    $extension = pathinfo($fileObject->getFilename(), PATHINFO_EXTENSION);

                    // Add all known template types (see #5857)
                    if (in_array($extension, $tplExts)) {
                        $relpath         = str_replace($root . '/', '', $fileObject->getPathname());
                        $key             = basename($relpath, strrchr($relpath, '.'));
                        $tplLoader[$key] = dirname($relpath);
                        $tplWidth        = max(strlen($key), $tplWidth);
                    }
                }
            }

            // Neither classes nor templates found
            if (empty($namespaces) && empty($classLoader) && empty($tplLoader)) {
                continue;
            }

            $package = ucfirst($module);

            // Start the PHP file
            $buffer =
<<<EOT
<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-$year Leo Feyer
 *
 * @package $package
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

EOT
            ;

            // Namespaces
            if (!empty($namespaces)) {
                $namespaces = array_unique($namespaces);

                if (!empty($namespaces)) {
                    $buffer .=
<<<EOT

// Namespaces
ClassLoader::addNamespaces(
[

EOT
                    ;

                    foreach ($namespaces as $namespace) {
                        $buffer .= "\t'" . $namespace . "',\n";
                    }

                    $buffer .= "]);\n";
                }
            }

            // Classes
            if (!empty($classLoader)) {
                $buffer .=
<<<EOT

// Classes
ClassLoader::addClasses(
[

EOT
                ;

                $group = null;

                foreach ($classLoader as $class=>$path) {
                    $relpath = str_replace('system/modules/' . $module . '/', '', $path);
                    $basedir = substr($relpath, 0, strpos($relpath, '/'));

                    if ($basedir != '') {
                        if ($group === null) {
                            $group = $basedir;
                            $buffer .= "\t// " . ucfirst($basedir) . "\n";
                        } elseif ($basedir != $group) {
                            $group = $basedir;
                            $buffer .= "\n\t// " . ucfirst($basedir) . "\n";
                        }
                    }

                    $class = "'" . $class . "'";
                    $buffer .= "\t" . str_pad($class, $classWidth+2) . " => '$path',\n";
                }

                $buffer .= "]);\n";
            }

            // Templates
            if (!empty($tplLoader)) {
                $buffer .=
<<<EOT

// Templates
TemplateLoader::addFiles(
[

EOT
                ;

                foreach ($tplLoader as $name=>$path) {
                    $name = "'" . $name . "'";
                    $buffer .= "\t" . str_pad($name, $tplWidth+2) . " => '$path',\n";
                }

                $buffer .= "]);\n";
            }

            // Generate the file
            $fs->dumpFile($root . '/system/modules/' . $module . '/config/autoload.php', $buffer);

            $output->writeln("  The autoload.php file of the $module module has been created.");
            unset($buffer);
        }
    }
}
