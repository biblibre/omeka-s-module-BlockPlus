<?php
namespace BlockPlus\Form\Element;

use Zend\Form\Element\Select;

class PartialSelect extends Select
{
    protected $templatePathStack = [];
    protected $theme = '';

    public function setOptions($options)
    {
        if (!empty($options['partial'])) {
            $options['value_options'] = $this->findPartials($options['partial']);
        }

        return parent::setOptions($options);
    }

    /**
     * Find all partials whose filename starts with a string.
     *
     * @param string $layout
     * @return array
     */
    protected function findPartials($layout)
    {
        // Hacky way to get all filenames for the asset. Theme first, then
        // modules, then core.
        $partials = [$layout => 'Default']; // @translate

        // Check filenames in core.
        $directory = OMEKA_PATH . '/application/view/';
        // Check filenames in modules.
        $recursiveList = $this->filteredFilesInFolder($directory, $layout, 'phtml');
        $partials += $recursiveList;

        // Check filenames in modules.
        foreach ($this->templatePathStack as $directory) {
            $recursiveList = $this->filteredFilesInFolder($directory, $layout, 'phtml');
            $partials += $recursiveList;
        }

        // Check filenames in the theme.
        if (strlen($this->theme)) {
            $directory = OMEKA_PATH . '/themes/' . $this->theme . '/view/';
            $recursiveList = $this->filteredFilesInFolder($directory, $layout, 'phtml');
            $partials += $recursiveList;
        }

        return $partials;
    }

    /**
     * Get files filtered by a path and extensions recursively in a directory.
     *
     * @param string $dir
     * @param string $layout Subdirectory or start of a file without extension.
     * @param string $extension
     * @return array Files are returned without extensions.
     */
    protected function filteredFilesInFolder($dir, $layout = '', $extension = '')
    {
        $base = rtrim($dir, '\\/') ?: '/';
        $layout = ltrim($layout, '\\/');

        $isLayoutDir = $layout === '' || substr($layout, -1) === '/';
        $dir = $isLayoutDir
            ? $base . '/' . $layout
            : dirname($base . '/' . $layout);
        if (empty($dir) || !file_exists($dir) || !is_dir($dir) || !is_readable($dir)) {
            return [];
        }

        // The files are saved from the base.
        $files = [];
        $dirLayout = $isLayoutDir
            ? $layout
            : (dirname($layout) ? dirname($layout) . '/' : '');
        $regex = '~' . preg_quote(pathinfo($layout, PATHINFO_FILENAME), '~')
            . '.*'
            . ($extension ? '\.' . preg_quote($extension, '~') : '')
            . '$~';
        $Directory = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $RegexIterator = new \RegexIterator($Iterator, $regex, \RecursiveRegexIterator::GET_MATCH);
        foreach ($RegexIterator as $file) {
            $file = reset($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (strlen($extension)) {
                $file = substr($file, 0, -1 - strlen($extension));
            }
            $files[$dirLayout . $file] = $file;
        }
        natcasesort($files);

        return $files;
    }

    /**
     * @param array $templatePathStack
     * @return \BlockPlus\Form\Element\PartialSelect
     */
    public function setTemplatePathStack(array $templatePathStack)
    {
        $this->templatePathStack = $templatePathStack;
        return $this;
    }

    /**
     * @param string $theme
     * @return \BlockPlus\Form\Element\PartialSelect
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }
}
