<div class="row mb-2">
    <div class="col">
        <template x-if="notifications.success.show">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <template x-if="notifications.success.url != ''">
                    <a :href="notifications.success.url" class="alert-link" x-text="notifications.success.text"></a>
                </template>
                <template x-if="notifications.success.url == ''">
                    <span x-text="notifications.success.text"></span>
                </template>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
            </div>
        </template>
        <template x-if="notifications.error.show">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <template x-if="notifications.error.url != ''">
                    <a :href="notifications.error.url" class="alert-link" x-text="notifications.error.text"></a>
                </template>
                <template x-if="notifications.error.url == ''">
                    <span x-text="notifications.error.text"></span>
                </template>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
            </div>
        </template>
        <template x-if="notifications.wait.show">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <em class="fa-solid fa-spinner fa-spin"></em>
                <span x-text="notifications.wait.text"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('firefly.close') }}"></button>
            </div>
        </template>
    </div>
</div>
