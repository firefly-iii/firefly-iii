<?php

/*
 * AutoBudgetType.php
 * Copyright (c) 2022 james@firefly-iii.org
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

 <?php

 declare(strict_types=1);
 
 namespace FireflyIII\Enums;
 
 /**
  * Enum AutoBudgetType
  * 
  * This enum represents different types of automatic budgeting operations.
  */
 enum AutoBudgetType: int
 {
     /** 
      * Represents the action of resetting the budget. 
      */
     case AUTO_BUDGET_RESET = 1;
 
     /** 
      * Represents the action of rolling over the budget from the previous period. 
      */
     case AUTO_BUDGET_ROLLOVER = 2;
 
     /** 
      * Represents the action of adjusting the budget based on certain criteria. 
      */
     case AUTO_BUDGET_ADJUSTED = 3;
 }
 