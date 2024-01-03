<div class="row mb-2">
    <div class="col">
        <template x-if="showSuccessMessage">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <a :href="successMessageLink" class="alert-link" x-text="successMessageText"></a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </template>
        <template x-if="showErrorMessage">
            <div class="alert alert-danger alert-dismissible fade show" role="alert"
                 x-text="errorMessageText">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </template>
        <template x-if="showWaitMessage">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <em class="fa-solid fa-spinner fa-spin"></em> Please wait for the attachments to upload.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </template>
    </div>
</div>
