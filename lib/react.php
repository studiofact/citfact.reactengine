<?php

/*
 * This file is part of the Studio Fact package.
 *
 * (c) Kulichkin Denis (onEXHovia) <onexhovia@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Citfact\ReactEngine;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Config\ConfigurationException;

class React
{
    /**
     * @var React
     */
    private static $instance = null;

    /**
     * @var \ReactJS
     */
    private $react;

    /**
     * @var array
     */
    private $defaultOptions = array(
        'pre_render' => true,
        'tag' => 'div'
    );

    /**
     * Constructor
     *
     * @param string $reactPath Path to ReactJS lib
     * @param string $appPath   Path to libs with available components
     */
    public function __construct($reactPath, $appPath)
    {
        $source = $this->getSource($reactPath, $appPath);
        $this->react = new \ReactJS($source['react'], $source['app']);
    }

    /**
     * Returns current instance of the React.
     *
     * @throw ConfigurationException When invalid REACT_SOURCE path
     *                               When invalid APP_SOURCE path
     *
     * @return React
     */
    public static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }

        $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'], "/\\");
        $moduleId = 'citfact.reactengine';

        $reactPath = sprintf('%s%s', $documentRoot, Option::get($moduleId, 'REACT_SOURCE'));
        $appPath = sprintf('%s%s', $documentRoot, Option::get($moduleId, 'APP_SOURCE'));

        static::$instance = new static($reactPath, $appPath);

        return static::$instance;
    }

    /**
     * Return source data
     *
     * @param string $reactPath
     * @param string $appPath
     * @throw ConfigurationException When invalid REACT_SOURCE path
     *                               When invalid APP_SOURCE path
     *
     * @return array
     */
    private function getSource($reactPath, $appPath)
    {
        if (empty($reactPath) || !is_file($reactPath)) {
            throw new ConfigurationException('Could not find the file specified from `REACT_SOURCE` parameters');
        }

        if (!empty($appPath) && !is_file($appPath)) {
            throw new ConfigurationException('Could not find the file specified from `APP_SOURCE` parameters');
        }

        $reactSource = file_get_contents($reactPath);
        $appSource = $appPath ? file_get_contents($appPath) : '';

        return array(
            'react' => $reactSource,
            'app' => $appSource,
        );
    }

    /**
     * Render a ReactJS component
     *
     * @param string $component Name of the component object
     * @param array $props      Associative array of props of the component
     * @param array $options    Associative array of options of rendering
     *
     * @return string
     */
    public function render($component, $props = null, $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);
        $tag = $options['tag'];
        $markup = '';

        // Creates the markup of the component
        if ($options['pre_render'] === true) {
            $markup = $this->react->setComponent($component, $props)->getMarkup();
        }

        // Pass props back to view as value of `data-react-props`
        $props = htmlentities(json_encode($props), ENT_QUOTES);

        // Gets all values that aren't used as options and map it as HTML attributes
        $htmlAttributes = array_diff_key($options, $this->defaultOptions);
        $htmlAttributesString = $this->arrayToHTMLAttributes($htmlAttributes);

        return "<{$tag} data-react-class='{$component}' data-react-props='{$props}' {$htmlAttributesString}>{$markup}</{$tag}>";
    }

    /**
     * Convert associative array to string of HTML attributes
     *
     * @param  array $array Associative array of attributes
     *
     * @return string
     */
    private function arrayToHTMLAttributes(array $array)
    {
        $htmlAttributesString = '';
        foreach ($array as $attribute => $value) {
            $htmlAttributesString .= "{$attribute}='{$value}'";
        }

        return $htmlAttributesString;
    }
}