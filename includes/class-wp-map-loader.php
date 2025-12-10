<?php
/**
 * 注册所有钩子并加载它们
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_Loader {
    /**
     * 存储动作钩子的数组
     */
    protected $actions;

    /**
     * 存储过滤器钩子的数组
     */
    protected $filters;

    /**
     * 初始化集合
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * 添加新的动作到集合
     */
    public function add_action($hook_name, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook_name, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 添加新的过滤器到集合
     */
    public function add_filter($hook_name, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook_name, $component, $callback, $priority, $accepted_args);
    }

    /**
     * 通用方法用于添加钩子
     */
    private function add($hooks, $hook_name, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook_name,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * 注册存储的钩子
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}