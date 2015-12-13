<?php
namespace Grav\Plugin;

use \Grav\Common\Plugin;

class MarkdownSectionsPlugin extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        $this->enable([
            'onPageInitialized' => ['onPageInitialized', 0],
        ]);
    }

    /**
     * Set needed variables to display breadcrumbs.
     */
    public function onPageInitialized()
    {
        $this->grav['twig']->twig_vars['sections'] = array();

        $path = $this->grav['page']->path();
        $sections["page"] = $this->parseDir($path);

        $sections["modular"] = array();
        $collection = $this->grav['page']->collection();
        $sections["modular"] = $this->parseChildren($collection);

        $this->grav['twig']->twig_vars['sections'] = $sections;
    }

    protected function parseDir($path)
    {
        $sections = array();
        $files = $this->scanDir($path);
        foreach($files as $fileName) {
            $sections[$fileName] = $this->parseFile($path, $fileName);
        }

        return $sections;
    }

    protected function parseFile($path, $fileName)
    {
        $sectionsFile = $path . '/' . $fileName;
        $sectionsContent = file_get_contents($sectionsFile);
        $regex = '/\[SECTION\s([^\]]+)\](.*?)\n\[\/SECTION\]/si';
        preg_match_all($regex, $sectionsContent, $matches, PREG_SET_ORDER);
        if (!$matches) {
            return array();
        }

        $defaults = $this->config->get('system.pages.markdown');
        if ($defaults['extra']) {
            $parsedown = new \ParsedownExtra();
        } else {
            $parsedown = new \Parsedown();
        }

        $sections = array();
        foreach($matches as $match) {
            $sectionName = $match[1];
            $sections[$sectionName] = $parsedown->text($match[2]);
        }

        return $sections;
    }

    protected function parseChildren($children)
    {
        if (is_array($children)) {
            return array();
        }

        $modules = $children->modular();
        if (count($modules) == 0) {
            return array();
        }

        $sections = array();
        foreach($children as $child) {
            $path = $child->path();
            $section = $this->parseDir($path);
            if (empty($section)) {
                continue;
            }

            $sections[basename($path)] = $section;
        }

        return $sections;
    }

    protected function scanDir($dir, $allowedExtensions = array('markdown'))
    {
        $files = array();
        $dh  = opendir($dir);
        while (false !== ($filename = readdir($dh))) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if ( ! in_array($ext, $allowedExtensions)) {
                continue;
            }

            $files[] = $filename;
        }

        return $files;
    }
}
