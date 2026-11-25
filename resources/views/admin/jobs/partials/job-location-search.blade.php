@if (config('services.google_maps.key'))
    @push('footer-script')
        <script>
            (() => {
                const fields = ['company_location', 'location_new']
                    .map(id => document.getElementById(id))
                    .filter(Boolean);
                const unavailableMessage = @json(__('Google Maps search is unavailable. Enter the location manually.'));
                const fieldStatuses = [];
                const jobCountry = document.getElementById('location_country_code');
                const streetAddress = document.getElementById('location_street_address');
                const cityInput = document.getElementById('location_city');
                const stateInput = document.getElementById('location_state');
                const postalCode = document.getElementById('location_postal_code');

                function setupLocationSearch(field, AutocompleteSuggestion, AutocompleteSessionToken) {
                    const countryInput = field.id === 'location_new' ? jobCountry : null;
                    const suggestions = document.createElement('datalist');
                    const status = document.createElement('div');
                    const predictions = new Map();
                    let timer;
                    let revision = 0;

                    field.autocomplete = 'off';
                    field.maxLength = 191;
                    suggestions.id = field.id + '-suggestions';
                    field.setAttribute('list', suggestions.id);
                    status.className = 'text-xs text-slate-500';
                    status.setAttribute('role', 'status');
                    field.after(suggestions, status);
                    fieldStatuses.push(status);

                    let sessionToken;

                    field.addEventListener('input', () => {
                        clearTimeout(timer);
                        const request = ++revision;
                        if (countryInput) {
                            countryInput.value = '';
                            delete field.dataset.locationLookupPending;
                        }
                        const input = field.value.trim();
                        const selected = predictions.get(input);
                        suggestions.replaceChildren();
                        predictions.clear();
                        status.textContent = '';

                        if (selected) {
                            sessionToken = undefined;
                            fillAddress(selected, request);
                            return;
                        }

                        if (input.length < 3) return;

                        timer = setTimeout(async () => {
                            try {
                                sessionToken ??= new AutocompleteSessionToken();
                                const result = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
                                    input,
                                    sessionToken,
                                });
                                if (request !== revision || field.value.trim() !== input) return;

                                for (const { placePrediction } of result.suggestions) {
                                    if (!placePrediction) continue;
                                    const label = placePrediction.text.toString();
                                    if (label.length > field.maxLength || predictions.has(label)) continue;
                                    predictions.set(label, placePrediction);
                                    const option = document.createElement('option');
                                    option.value = label;
                                    suggestions.append(option);
                                }
                                if (predictions.size) status.textContent = 'Google Maps';
                            } catch {
                                if (request === revision) showError();
                            }
                        }, 250);
                    });

                    async function fillAddress(prediction, request) {
                        const selectedValue = field.value;
                        const previousStreet = streetAddress?.value;
                        const previousCity = cityInput?.value;
                        const previousState = stateInput?.value;
                        const previousPostal = postalCode?.value;
                        if (countryInput) {
                            field.dataset.locationLookupPending = '1';
                            status.textContent = 'Reading location from Google Maps…';
                        }
                        try {
                            const place = prediction.toPlace();
                            await place.fetchFields({ fields: countryInput ? ['formattedAddress', 'addressComponents'] : ['formattedAddress'] });
                            if (request !== revision || field.value !== selectedValue) return;
                            if (countryInput) {
                                const component = type => place.addressComponents?.find(item => item.types.includes(type));
                                const code = component('country')?.shortText;
                                const route = component('route')?.longText;
                                const number = component('street_number')?.longText;
                                const street = route && number ? number + ' ' + route : '';
                                const city = (component('locality') ?? component('postal_town') ?? component('sublocality_level_1'))?.longText ?? '';
                                const state = component('administrative_area_level_1')?.longText ?? '';
                                const postal = component('postal_code')?.longText ?? '';
                                const suffix = component('postal_code_suffix')?.longText;
                                const fullPostal = postal && suffix && code === 'US' ? postal + '-' + suffix : postal;
                                if (streetAddress && streetAddress.value === previousStreet && street.length <= streetAddress.maxLength) {
                                    streetAddress.value = street;
                                }
                                if (cityInput && cityInput.value === previousCity && city.length <= cityInput.maxLength) {
                                    cityInput.value = city;
                                }
                                if (stateInput && stateInput.value === previousState && state.length <= stateInput.maxLength) {
                                    stateInput.value = state;
                                }
                                if (postalCode && postalCode.value === previousPostal && fullPostal.length <= postalCode.maxLength) {
                                    postalCode.value = fullPostal;
                                }
                                countryInput.value = /^[A-Z]{2}$/.test(code ?? '') ? code : '';
                                status.textContent = countryInput.value ? 'Country code: ' + countryInput.value : '';
                            }
                            if (place.formattedAddress && place.formattedAddress.length <= field.maxLength) {
                                field.value = place.formattedAddress;
                            }
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        } catch {
                            if (request === revision) showError();
                        } finally {
                            if (countryInput && request === revision) delete field.dataset.locationLookupPending;
                        }
                    }

                    function showError() {
                        status.textContent = unavailableMessage;
                    }
                }

                function showError() {
                    for (const status of fieldStatuses) {
                        status.textContent = unavailableMessage;
                    }
                }

                window.initCompanyLocationSearch = async () => {
                    try {
                        const { AutocompleteSuggestion, AutocompleteSessionToken } =
                            await google.maps.importLibrary('places');

                        for (const field of fields) {
                            setupLocationSearch(field, AutocompleteSuggestion, AutocompleteSessionToken);
                        }
                    } catch {
                        showError();
                    }
                };

                window.gm_authFailure = showError;
                const script = document.createElement('script');
                const url = new URL('https://maps.googleapis.com/maps/api/js');
                url.search = new URLSearchParams({
                    key: @json(config('services.google_maps.key')),
                    loading: 'async',
                    callback: 'initCompanyLocationSearch',
                });
                script.src = url;
                script.async = true;
                script.onerror = showError;
                document.head.append(script);
            })();
        </script>
    @endpush
@endif
