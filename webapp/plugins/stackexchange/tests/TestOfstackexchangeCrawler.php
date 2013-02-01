<?php
/**
 *
 * webapp/plugins/stackexchange/tests/TestOfstackexchangeCrawler.php
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
 * @author Alan Storm
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Alan Storm
 */
  
require_once 'tests/init.tests.php';
require_once THINKUP_ROOT_PATH.'webapp/_lib/extlib/simpletest/autorun.php';
require_once THINKUP_ROOT_PATH.'webapp/_lib/extlib/simpletest/web_tester.php';
require_once THINKUP_ROOT_PATH.'webapp/plugins/stackexchange/model/class.stackexchangeCrawler.php';

class TestOfstackexchangeCrawler extends ThinkUpUnitTestCase {

    public function setUp() {
        parent::setUp();
        $this->logger = Logger::getInstance();
    }

    public function tearDown() {
        parent::tearDown();
        $this->logger->close();
    }

}
