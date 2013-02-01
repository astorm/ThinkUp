<?php
/*
 Plugin Name: StackExchange
 Plugin URI: http://github.com/ginatrapani/thinkup/tree/master/webapp/plugins/stackexchange/
 Description: 
 Class: stackexchangePlugin
 Icon: assets/img/plugin_icon.png
 Version: 0.01
 Author: Alan Storm
 */
/**
 *
 * webapp/plugins/stackexchange/controller/stackexchange.php
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkupapp.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 * 
 *
 * stackexchange (name of file)
 *
 * Description of what this class does
 *
 * Copyright (c) 2012 Alan Storm
 * 
 * @author Your Name  Your Email
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Alan Storm
 */
 
$webapp_plugin_registrar = PluginRegistrarWebapp::getInstance();
$webapp_plugin_registrar->registerPlugin('stackexchange', 'stackexchangePlugin');

$crawler_plugin_registrar = PluginRegistrarCrawler::getInstance();
$crawler_plugin_registrar->registerCrawlerPlugin('stackexchangePlugin');

