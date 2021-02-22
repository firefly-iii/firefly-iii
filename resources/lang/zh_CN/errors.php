<?php

/**
 * firefly.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    '404_header'              => 'Firefly III 无法找到该页面',
    '404_page_does_not_exist' => '您请求的页面不存在，请确认您输入的网址正确无误。',
    '404_send_error'          => '如果您被自动跳转到该页面，很抱歉。日志文件中记录了该错误，请将错误信息提交给开发者，万分感谢。',
    '404_github_link'         => '如果您确信该页面应该存在，请在 <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong> 上创建工单。',
    'whoops'                  => '很抱歉',
    'fatal_error'             => '发生致命错误：请检查位于“storage/logs”目录的日志文件，或使用“docker logs -f [container]”命令查看相关信息。',
    'maintenance_mode'        => 'Firefly III 已启用维护模式',
    'be_right_back'           => '敬请期待！',
    'check_back'              => 'Firefly III 正在进行必要的维护，请稍后再试',
    'error_occurred'          => '很抱歉，出现错误',
    'error_not_recoverable'   => '很遗憾，该错误无法恢复 :( Firefly III 已崩溃。错误信息：',
    'error'                   => '错误',
    'error_location'          => '该错误位于文件 <span style="font-family: monospace;">:file</span> 第 :line 行的代码 :code',
    'stacktrace'              => '堆栈跟踪',
    'more_info'               => '更多信息',
    'collect_info'            => '请在 <code>storage/logs</code> 目录中查找日志文件以获取更多信息。如果您正在使用 Docker，请使用 <code>docker logs -f [container]</code>。',
    'collect_info_more'       => '您可以在<a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">FAQ页面</a>了解更多有关收集错误信息的内容。',
    'github_help'             => '在 GitHub 上获取帮助',
    'github_instructions'     => '欢迎您在 <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong> 创建工单。',
    'use_search'              => '请善用搜索功能！',
    'include_info'            => '请包含<a href=":link">该调试页面</a>的相关信息。',
    'tell_more'               => '请提交给我们更多信息，而不仅仅是“网页提示说很抱歉”。',
    'include_logs'            => '请包含错误日志（见上文）。',
    'what_did_you_do'         => '告诉我们您进行了哪些操作。',

];
