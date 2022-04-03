/*
 * routes.js
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

const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [{
      path: '',
      component: () => import('pages/Index.vue'),
      name: 'index',
      meta: {dateSelector: true, pageTitle: 'firefly.welcome_back',}
    }]
  },
  // beta
  {
    path: '/development',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/development/Index.vue'),
        name: 'development.index',
        meta: {
          pageTitle: 'firefly.development'
        }
      }
    ]
  },
  // beta
  {
    path: '/export',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/export/Index.vue'),
        name: 'export.index',
        meta: {
          pageTitle: 'firefly.export'
        }
      }
    ]
  },
  // budgets
  {
    path: '/budgets',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/budgets/Index.vue'),
        name: 'budgets.index',
        meta: {
          pageTitle: 'firefly.budgets',
          breadcrumbs: [
            {title: 'budgets', route: 'budgets.index', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/budgets/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/budgets/Show.vue'),
        name: 'budgets.show',
        meta: {
          pageTitle: 'firefly.budgets',
          breadcrumbs: [
            {title: 'placeholder', route: 'budgets.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/budgets/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/budgets/Edit.vue'),
        name: 'budgets.edit',
        meta: {
          pageTitle: 'firefly.budgets',
          breadcrumbs: [
            {title: 'placeholder', route: 'budgets.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/budgets/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/budgets/Create.vue'),
        name: 'budgets.create',
        meta: {
          pageTitle: 'firefly.budgets',
          breadcrumbs: [
            {title: 'placeholder', route: 'budgets.show', params: []}
          ]
        }
      }
    ]
  },
  // subscriptions
  {
    path: '/subscriptions',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/subscriptions/Index.vue'),
        name: 'subscriptions.index',
        meta: {
          pageTitle: 'firefly.subscriptions',
          breadcrumbs: [{title: 'placeholder', route: 'subscriptions.index', params: []}]
        }
      }
    ]
  },
  {
    path: '/subscriptions/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/subscriptions/Show.vue'),
        name: 'subscriptions.show',
        meta: {
          pageTitle: 'firefly.subscriptions',
          breadcrumbs: [
            {title: 'placeholder', route: 'subscriptions.index'},
          ]
        }
      }
    ]
  },
  {
    path: '/subscriptions/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/subscriptions/Edit.vue'),
        name: 'subscriptions.edit',
        meta: {
          pageTitle: 'firefly.subscriptions',
          breadcrumbs: [
            {title: 'placeholder', route: 'subscriptions.index'},
          ]
        }
      }
    ]
  },
  {
    path: '/subscriptions/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/subscriptions/Create.vue'),
        name: 'subscriptions.create',
        meta: {
          dateSelector: false,
          pageTitle: 'firefly.subscriptions',
        }
      }
    ]
  },
  // piggy banks
  {
    path: '/piggy-banks',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/piggy-banks/Index.vue'),
        name: 'piggy-banks.index',
        meta: {
          pageTitle: 'firefly.piggyBanks',
          breadcrumbs: [{title: 'piggy-banks', route: 'piggy-banks.index', params: []}]
        }

      }
    ]
  },
  {
    path: '/piggy-banks/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/piggy-banks/Create.vue'),
        name: 'piggy-banks.create',
        meta: {
          pageTitle: 'firefly.piggy-banks',
          breadcrumbs: [
            {title: 'placeholder', route: 'piggy-banks.create', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/piggy-banks/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/piggy-banks/Show.vue'),
        name: 'piggy-banks.show',
        meta: {
          pageTitle: 'firefly.piggy-banks',
          breadcrumbs: [
            {title: 'placeholder', route: 'piggy-banks.index'},
          ]
        }
      }
    ]
  },
  {
    path: '/piggy-banks/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/piggy-banks/Edit.vue'),
        name: 'piggy-banks.edit',
        meta: {
          pageTitle: 'firefly.piggy-banks',
          breadcrumbs: [
            {title: 'placeholder', route: 'piggy-banks.index'},
          ]
        }
      }
    ]
  },

  // transactions (single)
  {
    path: '/transactions/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/transactions/Show.vue'),
        name: 'transactions.show',
        meta: {
          pageTitle: 'firefly.transactions',
          breadcrumbs: [
            {title: 'placeholder', route: 'transactions.index', params: {type: 'todo'}},
            {title: 'placeholder', route: 'transactions.show', params: []}
          ]
        }
      }
    ]
  },
  // transactions (create)
  {
    path: '/transactions/create/:type',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/transactions/Create.vue'),
        name: 'transactions.create',
        meta: {
          dateSelector: false,
          pageTitle: 'firefly.transactions',
        }
      }
    ]
  },
  // transactions (index)
  {
    path: '/transactions/:type',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/transactions/Index.vue'),
        name: 'transactions.index',
        meta: {
          dateSelector: false,
          pageTitle: 'firefly.transactions',
          breadcrumbs: [
            {title: 'transactions'},
          ]
        }
      }
    ]
  },
  {
    path: '/transactions/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/transactions/Edit.vue'),
        name: 'transactions.edit',
        meta: {
          pageTitle: 'firefly.transactions',
          breadcrumbs: [
            {title: 'placeholder', route: 'transactions.index', params: {type: 'todo'}},
            {title: 'placeholder', route: 'transactions.show', params: []}
          ]
        }
      }
    ]
  },

  // rules
  {
    path: '/rules',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/rules/Index.vue'),
        name: 'rules.index',
        meta: {
          pageTitle: 'firefly.rules',
        }
      }
    ]
  },
  {
    path: '/rules/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/rules/Show.vue'),
        name: 'rules.show',
        meta: {
          pageTitle: 'firefly.rules',
          breadcrumbs: [
            {title: 'placeholder', route: 'transactions.index', params: {type: 'todo'}},
            {title: 'placeholder', route: 'transactions.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/rules/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/rules/Create.vue'),
        name: 'rules.create',
        meta: {
          pageTitle: 'firefly.rules',
          breadcrumbs: [
            {title: 'placeholder', route: 'transactions.index', params: {type: 'todo'}},
          ]
        }
      }
    ]
  },
  {
    path: '/rules/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/rules/Edit.vue'),
        name: 'rules.edit',
        meta: {
          pageTitle: 'firefly.rules',
          breadcrumbs: [
            {title: 'placeholder', route: 'rules.index', params: {type: 'todo'}},
          ]
        }
      }
    ]
  },
  {
    path: '/rule-groups/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/rule-groups/Edit.vue'),
        name: 'rule-groups.edit',
        meta: {
          pageTitle: 'firefly.rules',
          breadcrumbs: [
            {title: 'placeholder', route: 'transactions.index', params: {type: 'todo'}},
          ]
        }
      }
    ]
  },
  {
    path: '/rule-groups/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/rule-groups/Create.vue'),
        name: 'rule-groups.create',
        meta: {
          pageTitle: 'firefly.rule-groups',
          breadcrumbs: [
            {title: 'placeholder', route: 'transactions.index', params: {type: 'todo'}},
          ]
        }
      }
    ]
  },

  // recurring transactions
  {
    path: '/recurring',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/recurring/Index.vue'),
        name: 'recurring.index',
        meta: {
          pageTitle: 'firefly.recurrences',
        }
      }
    ]
  },
  {
    path: '/recurring/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/recurring/Create.vue'),
        name: 'recurring.create',
        meta: {
          pageTitle: 'firefly.recurrences',
          breadcrumbs: [
            {title: 'placeholder', route: 'recurrences.create', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/recurring/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/recurring/Show.vue'),
        name: 'recurring.show',
        meta: {
          pageTitle: 'firefly.recurrences',
          breadcrumbs: [
            {title: 'placeholder', route: 'recurrences.index'},
          ]
        }
      }
    ]
  },
  {
    path: '/recurring/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/recurring/Edit.vue'),
        name: 'recurring.edit',
        meta: {
          pageTitle: 'firefly.recurrences',
          breadcrumbs: [
            {title: 'placeholder', route: 'recurrences.index'},
          ]
        }
      }
    ]
  },

  // accounts
  // account (single)
  {
    path: '/accounts/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/accounts/Show.vue'),
        name: 'accounts.show',
        meta: {
          pageTitle: 'firefly.accounts',
          breadcrumbs: [
            {title: 'placeholder', route: 'accounts.index', params: {type: 'todo'}},
            {title: 'placeholder', route: 'accounts.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/accounts/reconcile/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/accounts/Reconcile.vue'),
        name: 'accounts.reconcile',
        meta: {
          pageTitle: 'firefly.accounts',
          breadcrumbs: [
            {title: 'placeholder', route: 'accounts.index', params: {type: 'todo'}},
            {title: 'placeholder', route: 'accounts.reconcile', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/accounts/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/accounts/Edit.vue'),
        name: 'accounts.edit',
        meta: {
          pageTitle: 'firefly.accounts',
          breadcrumbs: [
            {title: 'placeholder', route: 'accounts.index', params: {type: 'todo'}},
            {title: 'placeholder', route: 'accounts.edit', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/accounts/:type',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/accounts/Index.vue'),
        name: 'accounts.index',
        meta: {
          pageTitle: 'firefly.accounts',
        }
      }
    ]
  },
  {
    path: '/accounts/create/:type',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/accounts/Create.vue'),
        name: 'accounts.create',
        meta: {
          pageTitle: 'firefly.accounts',
        }
      }
    ]
  },

  // categories
  {
    path: '/categories',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/categories/Index.vue'),
        name: 'categories.index',
        meta: {
          pageTitle: 'firefly.categories',
        }
      }
    ]
  },
  {
    path: '/categories/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/categories/Show.vue'),
        name: 'categories.show',
        meta: {
          pageTitle: 'firefly.categories',
          breadcrumbs: [
            {title: 'placeholder', route: 'categories.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/categories/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/categories/Edit.vue'),
        name: 'categories.edit',
        meta: {
          pageTitle: 'firefly.categories',
          breadcrumbs: [
            {title: 'placeholder', route: 'categories.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/categories/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/categories/Create.vue'),
        name: 'categories.create',
        meta: {
          pageTitle: 'firefly.categories',
          breadcrumbs: [
            {title: 'placeholder', route: 'categories.show', params: []}
          ]
        }
      }
    ]
  },
  // tags
  {
    path: '/tags',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/tags/Index.vue'),
        name: 'tags.index',
        meta: {
          pageTitle: 'firefly.tags',
        }
      }
    ]
  },
  {
    path: '/tags/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/tags/Show.vue'),
        name: 'tags.show',
        meta: {
          pageTitle: 'firefly.tags',
          breadcrumbs: [
            {title: 'placeholder', route: 'tags.show', params: []}
          ]
        }
      }
    ]
  },

  // groups
  {
    path: '/groups',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/groups/Index.vue'),
        name: 'groups.index',
        meta: {
          pageTitle: 'firefly.object_groups_page_title'
        }
      }
    ]
  },
  {
    path: '/groups/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/groups/Show.vue'),
        name: 'groups.show',
        meta: {
          pageTitle: 'firefly.groups',
          breadcrumbs: [
            {title: 'placeholder', route: 'groups.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/groups/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/groups/Edit.vue'),
        name: 'groups.edit',
        meta: {
          pageTitle: 'firefly.groups',
          breadcrumbs: [
            {title: 'placeholder', route: 'categories.show', params: []}
          ]
        }
      }
    ]
  },
  // reports
  {
    path: '/reports',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/reports/Index.vue'),
        name: 'reports.index',
        meta: {
          pageTitle: 'firefly.reports'
        }
      }
    ]
  },
  {
    path: '/report/default/:accounts/:start/:end',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/reports/Default.vue'),
        name: 'reports.default',
        meta: {
          pageTitle: 'firefly.reports'
        }
      }
    ]
  },

  // webhooks
  {
    path: '/webhooks',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/webhooks/Index.vue'),
        name: 'webhooks.index',
        meta: {
          pageTitle: 'firefly.webhooks'
        }
      }
    ]
  },
  {
    path: '/webhooks/show/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/webhooks/Show.vue'),
        name: 'webhooks.show',
        meta: {
          pageTitle: 'firefly.webhooks',
          breadcrumbs: [
            {title: 'placeholder', route: 'groups.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/webhooks/edit/:id',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/webhooks/Edit.vue'),
        name: 'webhooks.edit',
        meta: {
          pageTitle: 'firefly.webhooks',
          breadcrumbs: [
            {title: 'placeholder', route: 'groups.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/webhooks/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/webhooks/Create.vue'),
        name: 'webhooks.create',
        meta: {
          pageTitle: 'firefly.webhooks',
          breadcrumbs: [
            {title: 'placeholder', route: 'webhooks.show', params: []}
          ]
        }
      }
    ]
  },

  // currencies
  {
    path: '/currencies',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/currencies/Index.vue'),
        name: 'currencies.index',
        meta: {
          pageTitle: 'firefly.currencies'
        }
      }
    ]
  },
  {
    path: '/currencies/show/:code',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/currencies/Show.vue'),
        name: 'currencies.show',
        meta: {
          pageTitle: 'firefly.currencies',
          breadcrumbs: [
            {title: 'placeholder', route: 'currencies.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/currencies/edit/:code',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/currencies/Edit.vue'),
        name: 'currencies.edit',
        meta: {
          pageTitle: 'firefly.currencies',
          breadcrumbs: [
            {title: 'placeholder', route: 'currencies.show', params: []}
          ]
        }
      }
    ]
  },
  {
    path: '/currencies/create',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/currencies/Create.vue'),
        name: 'currencies.create',
        meta: {
          pageTitle: 'firefly.currencies',
          breadcrumbs: [
            {title: 'placeholder', route: 'currencies.create', params: []}
          ]
        }
      }
    ]
  },
  // profile
  {
    path: '/profile',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/profile/Index.vue'),
        name: 'profile.index',
        meta: {
          pageTitle: 'firefly.profile'
        }
      }
    ]
  },
  {
    path: '/profile/data',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/profile/Data.vue'),
        name: 'profile.data',
        meta: {
          pageTitle: 'firefly.profile_data'
        }
      }
    ]
  },

  // preferences
  {
    path: '/preferences',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/preferences/Index.vue'),
        name: 'preferences.index',
        meta: {
          pageTitle: 'firefly.preferences'
        }
      }
    ]
  },

  // administration
  {
    path: '/admin',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/admin/Index.vue'),
        name: 'admin.index',
        meta: {
          pageTitle: 'firefly.administration'
        }
      }
    ]
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/Error404.vue')
  }
]

export default routes
