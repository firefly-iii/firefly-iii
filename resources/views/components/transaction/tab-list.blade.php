<div class="row">
    <div class="col">

        <ul class="nav nav-tabs" id="splitTabs" role="tablist">
            <template x-for="transaction,index in entries">
                <li class="nav-item" role="presentation">
                    <button :id="'split-'+index+'-tab'"
                            :class="{'nav-link': true, 'active': index === 0 }"
                            data-bs-toggle="tab"
                            :data-bs-target="'#split-'+index+'-pane'"
                            type="button" role="tab"
                            :aria-controls="'split-'+index+'-pane'"
                            aria-selected="true">
                        <template x-if="'' === transaction.description">
                            <span>{{ __('firefly.single_split') }} #<span x-text="index+1"></span></span>
                        </template>
                        <template x-if="'' !== transaction.description">
                            <span x-text="transaction.description"></span>
                        </template>
                    </button>
                </li>
            </template>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" @click="addSplit()"
                ><em class="bi bi-plus-circle"></em>
                </button>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" aria-disabled="true">
                    {{ __('firefly.total') }}:
                    <span x-text="formattedTotalAmount()"></span>
                </a>
            </li>


        </ul>
    </div>
</div>
