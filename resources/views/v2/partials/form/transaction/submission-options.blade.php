<div class="form-check">
    <input class="form-check-input" x-model="returnHereButton" type="checkbox"
           id="returnButton">
    <label class="form-check-label" for="returnButton">
        Return here to create a new transaction
    </label>
</div>

<div class="form-check">
    <input class="form-check-input" x-model="resetButton" type="checkbox"
           id="resetButton" :disabled="!returnHereButton">
    <label class="form-check-label" for="resetButton">
        Reset the form after returning
    </label>
</div>

<div class="form-check">
    <input class="form-check-input" type="checkbox" id="rulesButton"
           :checked="rulesButton">
    <label class="form-check-label" for="rulesButton">
        Run rules on this transaction
    </label>
</div>

<div class="form-check">
    <input class="form-check-input" type="checkbox" id="webhookButton"
           :checked="webhookButton">
    <label class="form-check-label" for="webhookButton">
        Run webhooks on this transaction
    </label>
</div>
