<!--
  - Index.vue
  - Copyright (c) 2022 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-7">
        <p>
          Hi! With your active support and feedback I'm capable of building this fancy new layout. So thank you for testing and playing around.
          I'm grateful for your help.
        </p>
        <p>
          The <strong>v2</strong> layout was built to be perfect for each page. This new <strong>v3</strong> layout has a different approach. I'm
          building a "minimum viable product", where each page has <em>minimal</em> functionality. But any functionality that's there should work. It
          may not do everything you need and stuff may be missing. The things that you see are things that work.
        </p>
        <p>
          If you spot problems, feel free to report them. Here are some known issues.
        </p>
        <ul>
          <li class="text-negative">You will lose data when you edit certain objects;</li>
          <li>Caching is fairly aggressive and a page refresh may be necessary to get new information. This is especially obvious when you make new transactions
            or accounts;
          </li>
          <li>Not all menu's are (un)folded correctly for all pages;</li>
          <li>Breadcrumbs are missing or incorrect;</li>
          <li>You can't make transaction splits;</li>
          <li>Accounts, budgets, transactions, etc. have only limited fields available in the edit, create and view screens;</li>
          <li>Occasionally, you may spot a "TODO". I've limited their presence, but sometimes I just need a placeholder;</li>
          <li>Missing translations, <code>firefly.abc</code> references, or transactions formatted in another locale;</li>
        </ul>
        <p>
          If you need to visit a <strong>v1</strong> alternative for the page you are seeing, please change the URL to
          <code>*/profile</code> (where <code>*</code> is your Firefly III URL). From there, you can navigate to any <strong>v1</strong> page.
          You may not be able to visit the v1 dashboard.
        </p>
        <p>
          Tickets on GitHub that concern v3 will be <em class="text-negative">closed</em>. This rule may change in the future. Until then, please leave your feedback here:
        </p>
        <ul>
          <li><a href="https://github.com/firefly-iii/firefly-iii/discussions/5589">GitHub discussion</a></li>
          <li><a href="https://gitter.im/firefly-iii/firefly-iii">Gitter.im chat</a></li>
          <li><a href="mailto:james@firefly-iii.org">james@firefly-iii.org</a></li>
        </ul>
        <p>
          Thanks again,<br>
          James
        </p>
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: "Index"
}
</script>

<style scoped>

</style>
