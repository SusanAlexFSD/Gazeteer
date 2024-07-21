var map;
var latitude, longitude;
var airportMarkers, restaurantMarkers, museumMarkers;

// Initialize marker clusters
var airportsCluster = L.markerClusterGroup();
var restaurantCluster = L.markerClusterGroup();
var museumsCluster = L.markerClusterGroup();

// Layer groups for each category
var airportsLayerGroup = L.layerGroup().addLayer(airportsCluster);
var restaurantLayerGroup = L.layerGroup().addLayer(restaurantCluster);
var museumsLayerGroup = L.layerGroup().addLayer(museumsCluster);

// Tile layers
var streets = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", {
    attribution: "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012"
});

var satellite = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
    attribution: "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community"
});

// Base maps
var basemaps = {
    "Streets": streets,
    "Satellite": satellite
};

// Overlay layers
var overlays = {
    "Airports": airportsLayerGroup,
    "Restaurant": restaurantLayerGroup,
    "Museums": museumsLayerGroup
};

// Map initialization
map = L.map("map", {
    layers: [streets] // Initialize with streets layer
}).setView([54.5, -4], 6);

var layerControl = L.control.layers(basemaps, overlays).addTo(map);

// Add default layers to the map
map.addLayer(airportsLayerGroup);
map.addLayer(restaurantLayerGroup);
map.addLayer(museumsLayerGroup);

// Ensure checkboxes are checked by default
$('#toggleAirports').prop('checked', true);
$('#toggleRestaurant').prop('checked', true);
$('#toggleMuseums').prop('checked', true);



//PRELOADER

document.addEventListener("DOMContentLoaded", function() {
    const preloader = document.getElementById('preloader');
    const mainContent = document.getElementById('main-content');

    if (preloader && mainContent) {
        // Hide preloader and show main content after 3 seconds
        setTimeout(() => {
            // Hide preloader
            preloader.style.display = 'none';
            
            // Show main content
            mainContent.classList.remove('hidden');
        }, 3000); // 3000 milliseconds = 3 seconds
    } else {
        if (!preloader) {
            console.error("preloader is null or undefined.");
        }
        if (!mainContent) {
            console.error("mainContent is null or undefined.");
        }
    }
});


// AJAX to fetch GeoJSON data
$.ajax({
    url: 'php/countryBorders.php',
    dataType: 'json',
    success: function (data) {
        if (data && !data.error && Array.isArray(data.features)) {
            var countryData = data.features.map(function (feature) {
                return {
                    name: feature.properties.name,
                    iso_a2: feature.properties.iso_a2,
                    iso_a3: feature.properties.iso_a3
                };
            });

            countryData.sort(function (a, b) {
                return a.name.localeCompare(b.name);
            });

            var dropdown = $('#countryDropdown');
            dropdown.empty();
            $.each(countryData, function (index, country) {
                dropdown.append($('<option></option>').text(country.name).data('iso_a2', country.iso_a2).data('iso_a3', country.iso_a3));
            });

            $('#countryDropdown').on('change', function () {
                let countryName = $(this).val();
                let iso_a2 = $(this).find('option:selected').data('iso_a2');
                let iso_a3 = $(this).find('option:selected').data('iso_a3');
                let countryCodeOrName = iso_a2 || iso_a3 || countryName;

                drawCountryBorder(countryCodeOrName);
                getCountryInfo(countryCodeOrName);
                getCoordinates(countryCodeOrName);
                getCapitalCoordinates(countryCodeOrName);  // Call the new function here
                fetchPOIData(countryCodeOrName);
            });

            // Call the function to get the user's location
            getUserLocation();

        } else {
            console.error('No features found in GeoJSON data');
        }
    },
    error: function (xhr, status, error) {
        console.error('Error fetching GeoJSON data:', error);
    }
});


// DRAW COUNTRY BORDER
var currentCountryBorder = null;

// Function to draw the country border
function drawCountryBorder(countryCode) {
    clearUserLocationMarkers();

    // Remove existing country border layer if it exists
    if (currentCountryBorder) {
        map.removeLayer(currentCountryBorder);
    }

    $.ajax({
        url: 'php/countryFeatures.php',
        dataType: 'json',
        data: { countryCode: countryCode },  // Ensure the country code is sent
        success: function (data) {
            var countryFeature = data.features.find(function (feature) {
                return feature.properties.iso_a2 === countryCode || feature.properties.iso_a3 === countryCode;
            });

            if (countryFeature) {
                currentCountryBorder = L.geoJSON(countryFeature.geometry, {
                    style: {
                        color: 'red',
                        weight: 1,
                        opacity: 1,
                        fill: false,
                        fillOpacity: 0
                    }
                }).addTo(map);
                
                // Fit the map to the bounds of the country
                var bounds = currentCountryBorder.getBounds();
                map.fitBounds(bounds);
            } else {
                console.error('Country borders not found for:', countryCode);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching GeoJSON data:', error);
        }
    });
}

var userLocationMarker;

// Function to clear markers from the user's location
function clearUserLocationMarkers() {
    // Remove any existing markers from the map
    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
        // Draw the country border after clearing the user location marker
        drawCountryBorder($('#countryDropdown').val());
    }
}

// USERS LOCATION 

function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(success, error);
    } else {
        console.log('Geolocation is not supported by this browser.');
    }
}

function success(position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;

    // Clear existing markers from the user's location
    clearUserLocationMarkers();

    // Make a request to the PHP script
    fetch(`php/userLocation.php?lat=${lat}&lon=${lon}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.countryCode) {
                const countryCode = data.countryCode.toUpperCase();
                getCountryInfo(countryCode);
                selectCountryInDropdown(countryCode); // Select the country in the dropdown
            } else {
                console.log('Unable to determine country code.');
            }
        })
        .catch(error => console.log('Request failed', error));
}

function error() {
    console.log('Unable to retrieve your location.');
}

// Function to select the country in the dropdown
function selectCountryInDropdown(countryCode) {
    $('#countryDropdown option').each(function () {
        if ($(this).data('iso_a2') === countryCode || $(this).data('iso_a3') === countryCode) {
            $(this).prop('selected', true);
            $('#countryDropdown').trigger('change'); // Trigger the change event
            return false; // Break the loop
        }
    });
}

// COUNTRY INFO

function getCountryInfo(countryCodeOrName) {
    $.ajax({
        url: 'php/countryInfo.php',
        method: 'GET',
        data: { countryCode: countryCodeOrName },
        success: function (response) {
            displayCountryInfo(response);
            setModalTitle(response);
            getCapitalCoordinates(countryCodeOrName);
        },
        error: function (xhr, status, error) {
            console.error('Error fetching country information:', error);
            $('#countryInfo').html('Error: Failed to fetch country information.');
        }
    });
}

// Function to display country info
function displayCountryInfo(result) {
    if (result.status && result.status.code === "200") {
        const countryData = result.data.geonames[0];
        if (countryData) {
            $('#txtCountry').text(countryData.countryName);
            $('#txtCapital').text(countryData.capital);
            $('#txtContinent').text(countryData.continentName);
            $('#txtPopulation').text(countryData.population);
            $('#txtLanguages').text(countryData.languages);
            $('#txtCurrencyCode').text(countryData.currencyCode);
            $('#txtisoAlpha3').text(countryData.isoAlpha3);
            // Format area with commas
            const formattedArea = formatNumberWithCommas(countryData.areaInSqKm);
            $('#txtArea').text(formattedArea);
        } else {
            console.error("Error:", result.status.description);
        }
    } else {
        console.error("Error:", result.status.description);
    }
}

// Function to format number with commas
function formatNumberWithCommas(number) {
    return new Intl.NumberFormat().format(number);
}

// Function to set the modal title
function setModalTitle(result) {
    if (result.status && result.status.code === "200") {
        const countryData = result.data.geonames[0];
        if (countryData) {
            const flagIcon = `<i class="fa-solid fa-flag"></i>`;
            const countryName = countryData.countryName;
            const formattedCountryName = `<span class="flag-icon">${flagIcon} ${countryName}</span>`;
            $('.modal-title').html(`${flagIcon} ${countryName}`);
        } else {
            console.error("Error:", result.status.description);
        }
    } else {
        console.error("Error:", result.status.description);
    }
}



// COORDINATES

function getCoordinates(country) {
    console.log("Fetching coordinates for:", country); // Debugging statement

    $.ajax({
        url: 'php/coordinatesInfo.php',
        method: 'GET',
        dataType: 'json',
        data: { country: country },
        success: function (response) {
            console.log("Coordinates response:", response); // Debugging statement

            if (response.status.code === "200") {
                let latitude = parseFloat(response.data.latitude);
                let longitude = parseFloat(response.data.longitude);
                console.log("Setting map view to:", latitude, longitude); // Debugging statement

                map.setView([latitude, longitude], 6);
                getWeather(latitude, longitude);
            } else {
                console.error("Error:", response.status.description);
                $('#countryInfo').html('Error: ' + response.status.description);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching coordinates:', error);
            $('#countryInfo').html('Error: Failed to fetch coordinates.');
        }
    });
}

// CITY COORDINATES

// Update the getCapitalCoordinates function to pass the country name and city to getWeather
function getCapitalCoordinates(countryCodeOrName) {
    $.ajax({
        url: 'php/countryInfo.php',
        method: 'GET',
        dataType: 'json',
        data: { countryCode: countryCodeOrName },
        success: function (response) {
            if (response.status && response.status.code === "200") {
                const countryData = response.data.geonames[0];
                if (countryData) {
                    const capitalLatitude = parseFloat(countryData.capitalLatitude);
                    const capitalLongitude = parseFloat(countryData.capitalLongitude);
                    const capitalCity = countryData.capital;
                    const countryName = countryData.countryName;

                    if (!isNaN(capitalLatitude) && !isNaN(capitalLongitude)) {
                        console.log("Capital city:", capitalCity);
                        console.log("Capital coordinates:", capitalLatitude, capitalLongitude);

                        // Update modal title with country and city name
                        $('#weatherModalTitle').html(`${countryName} - ${capitalCity}`);

                        // Set paragraph with country and city name
                        $('#weatherLocation').text(`Weather in ${capitalCity}, ${countryName}`);

                        // Call getWeather with capital coordinates, city name, and country name
                        getWeather(capitalLatitude, capitalLongitude, capitalCity, countryName);
                    } else {
                        console.error("Error: Invalid capital coordinates.");
                    }
                } else {
                    console.error("Error: Country data not found.");
                }
            } else {
                console.error("Error fetching capital coordinates:", response.status.description);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching capital coordinates:', error);
        }
    });
}



    // WEATHER

    function getWeather(capitalLatitude, capitalLongitude, capitalCity, countryName) {
        $.ajax({
            url: 'php/weatherInfo.php',
            method: 'GET',
            data: { lat: capitalLatitude, lon: capitalLongitude },
            success: function (response) {
                displayWeatherInfo(response, capitalCity, countryName);
            },
            error: function (xhr, status, error) {
                console.error('Error fetching weather information:', error);
                $('#weatherInfo').html('Error: Failed to fetch weather information.');
            }
        });
    }
    
    function displayWeatherInfo(result, capitalCity, countryName) {
        if (result && result.data && result.data.current && result.data.forecast && result.data.forecast.forecastday.length > 0) {
            const currentWeather = result.data.current;
            const forecastDays = result.data.forecast.forecastday;
    
            $('#weatherModalLabel').html(`${capitalCity}, ${countryName}`);
    
            const iconUrl = `http:${currentWeather.condition.icon}`;
            const tempCelsius = Math.round(currentWeather.temp_c);
            const feelsLikeCelsius = Math.round(currentWeather.feelslike_c);
            const tempMax = Math.round(forecastDays[0].day.maxtemp_c);
            const tempMin = Math.round(forecastDays[0].day.mintemp_c);
    
            const currentWeatherHtml = `
                <tr>
                    <td colspan="3"><h5>Today</h5></td>
                </tr>
                <tr>
                    <td>${currentWeather.condition.text}</td>
                    <td><img src="${iconUrl}" alt="Weather Icon"></td>
                    <td>${tempMin} °C / ${tempMax} °C</td>
                </tr>`;
    
            $('#currentWeather').html(currentWeatherHtml);
    
            // Display forecast for the next 2 days
            let forecastHtml = '';
            for (let i = 1; i < 3; i++) {
                const forecast = forecastDays[i];
                const date = new Date(forecast.date);
                const day = date.toLocaleString('en-US', { weekday: 'short' });
                const dayOfMonth = date.getDate();
                const suffix = (dayOfMonth >= 11 && dayOfMonth <= 13) ? 'th' : ['th', 'st', 'nd', 'rd', 'th'][Math.min(dayOfMonth % 10, 4)];
                const formattedDate = `${day} ${dayOfMonth}${suffix}`;
                const tempMax = Math.round(forecast.day.maxtemp_c);
                const tempMin = Math.round(forecast.day.mintemp_c);
                const condition = forecast.day.condition.text;
                const icon = `http:${forecast.day.condition.icon}`;
    
                forecastHtml += `
                    <div class="col-6">
                        <div class="forecast-day">
                            <h6>${formattedDate}</h6>
                            <div class="d-flex align-items-center">
                                <img src="${icon}" alt="Weather Icon" class="me-2">
                                <div>
                                    <p class="mb-0">${condition}</p>
                                    <p class="mb-0">${tempMin} °C / ${tempMax} °C</p>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }
    
            $('#forecast').html(forecastHtml);
        } else {
            console.error("Error: Invalid weather data format.");
        }
    }
    



    

    
//CURRENCY
$(document).ready(function() {
    var defaultAmount = 1; // Default amount to pre-populate

    // Function to handle currency conversion
    function handleCurrencyConversion() {
        var amount = $('#conversion_amount_modal').val();
        var fromCurrency = $('#fromCurrency_modal').val();
        var toCurrency = $('#toCurrency_modal').val();

        if (amount && fromCurrency && toCurrency) {
            $.ajax({
                url: 'php/currencyConversion.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    amount: amount,
                    baseCode: fromCurrency,
                    targetCode: toCurrency
                },
                success: function (response) {
                    console.log('Currency conversion response:', response);
                    if (response && response.result === 'success') {
                        $('#conversionResult_modal').text('Conversion Result: ' + response.conversion_result).show();
                    } else {
                        console.error('Error: Unable to perform currency conversion');
                        $('#conversionRate_modal').hide();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error performing currency conversion:', error);
                    $('#conversionRate_modal').hide();
                }
            });
        } else {
            $('#conversionRate_modal').hide();
        }
    }

    // Reset the amount input field and conversion result
    function resetCurrencyModal() {
        $('#conversion_amount_modal').val(defaultAmount).attr('placeholder', 'Enter amount');
        $('#conversionResult_modal').hide();
    }

    // Populate currency dropdowns
    function populateCurrencyDropdowns() {
        $.ajax({
            url: 'php/currencyInfo.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log('Currency options response:', response);
                if (response && response.result === 'success') {
                    $('#fromCurrency_modal').empty();
                    $('#toCurrency_modal').empty();
                    
                    $.each(response.currencies, function (key, value) {
                        $('#fromCurrency_modal').append($('<option></option>').val(key).text(key));
                        $('#toCurrency_modal').append($('<option></option>').val(key).text(key));
                    });

                    $('#fromCurrency_modal').val(response.baseCode);
                    $('#toCurrency_modal').val(response.targetCode);
                } else {
                    console.error('Error: Unable to fetch currency options');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching currency options:', error);
            }
        });
    }

    // Populate currency dropdowns on page load
    populateCurrencyDropdowns();

    // Bind currency conversion function to keyup and change events
    $('#conversion_amount_modal, #fromCurrency_modal, #toCurrency_modal').on('keyup change', function () {
        handleCurrencyConversion();
    });

    // Pre-populate the amount and perform conversion when the modal is shown
    $('#currencyInfoResults').on('show.bs.modal', function () {
        $('#conversion_amount_modal').val(defaultAmount);
        handleCurrencyConversion();
    });

    // Reset the amount field when the modal is hidden
    $('#currencyInfoResults').on('hidden.bs.modal', function () {
        resetCurrencyModal();
    });
});





///WIKIPEDIA


    // Event handler for Wikipedia button click
    // Bind the AJAX request to fetch Wikipedia data to the shown.bs.modal event of the modal
$('#wikipediaInfoResults').on('shown.bs.modal', function(event) {
    // Get the selected country from the dropdown
    var selectedCountry = $("#countryDropdown").val();
    // Call the PHP script to fetch Wikipedia data
    $.ajax({
        url: 'php/wikipediaInfo.php', // Path to your PHP file
        type: 'GET',
        data: {
            country: selectedCountry
        },
        success: function(response) {
            // Display the response in the modal
            $("#wikiContent").html(response);
        },
        error: function(xhr, status, error) {
            alert('Error fetching data from Wikipedia API');
        }
    });
});

// Event handler for close button click for Wikipedia modal
$(document).on("click", "#wikipediaCloseBtn", function() {
    console.log("Wikipedia Close button clicked!");
    $("#wikipediaInfoResults").modal("hide");
});

//NEWS

    // Function to fetch news based on country code

function fetchNews(countryCode) {
    console.log("Fetching news for country code:", countryCode);
    $.ajax({
        url: 'php/newsInfo.php', 
        method: 'GET',
        data: {
            countryCode: countryCode,
        },
        dataType: 'json',
        success: function (response) {
            console.log("News received:", response);
            if (response.error) {
                console.error("Error fetching news:", response.error);
            } else if (response.results) {
                console.log("Results:", response.results);
                displayNews(response.results); // Update to pass the 'results' array
            } else {
                console.error("No results found in the response.");
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching news:", error);
        },
        complete: function(xhr, status) {
            console.log("Raw response data:", xhr.responseText);
        }
    });
}


    // Function to display news articles
    function displayNews(response) {
        $('#newsInfoBody').empty();

        if (Array.isArray(response)) {
            response.forEach(function(article) {
                var articleContainer = $('<div class="article"></div>');

                if (article.image_url) {
                    var image = $('<img>').attr('src', article.image_url);
                    articleContainer.append(image);
                }

                var articleContent = $('<div class="article-content"></div>');
                console.log("Article URL:", article.link); // Use 'link' property instead of 'full_url'
                var titleLink = $('<a class="article-title" target="_blank"></a>').attr('href', article.link).text(article.title); // Use 'link' property instead of 'full_url'
                articleContent.append(titleLink);

                articleContainer.append(articleContent);
                $('#newsInfoBody').append(articleContainer);
            });
        } else {
            console.error("Invalid response structure. Unable to display news.");
        }
    }

    // Event handler for showing the news modal
    $('#newsInfoResults').on('shown.bs.modal', function(event) {
        console.log("News modal shown!");
        var countryCode = $('#countryDropdown').find('option:selected').data('iso_a2');
        // Fetch news information for the selected country
        fetchNews(countryCode);
    });

    // Event listener for links inside the news modal
    $('#newsInfoResults').on('click', 'a', function(event) {
        // Prevent the default behavior of the link
        event.preventDefault();
        // Get the URL of the clicked link
        var url = $(this).attr('href'); // Retrieve URL from href attribute
        // Open the URL in a new tab
        window.open(url, '_blank');
    });

//POI 

    var airportMarkers, restaurantMarkers, museumMarkers; // Define markers globally

$('#countryDropdown').on('change', function() {
    var countryCode = $(this).find('option:selected').data('iso_a2');
    fetchPOIData(countryCode);
});

function fetchPOIData(countryCode) {
    airportsCluster.clearLayers();
    restaurantCluster.clearLayers();
    museumsCluster.clearLayers();

    $.ajax({
        url: 'php/poiInfo.php',
        method: 'GET',
        dataType: 'json',
        data: { countryCode: countryCode },
        success: function(response) {
            var countryBounds = response.countryBounds;
            if (countryBounds && Array.isArray(countryBounds)) {
                var bounds = L.latLngBounds(
                    L.latLng(countryBounds[0], countryBounds[1]),
                    L.latLng(countryBounds[2], countryBounds[3])
                );
                map.fitBounds(bounds);
            }

            displayAirportInfo(response.airport);
            displayRestaurantInfo(response.restaurant);
            displayMuseumInfo(response.museum);

            map.addLayer(airportsLayerGroup);
            map.addLayer(restaurantLayerGroup);
            map.addLayer(museumsLayerGroup);
        },
        error: function(xhr, status, error) {
            console.error('Error fetching POI information:', error);
        }
    });
}

function displayAirportInfo(airportData) {
    if (airportData && Array.isArray(airportData.geonames)) {
        airportData.geonames.forEach(function(airport) {
            if (airport.lat && airport.lng) {
                var marker = L.marker([parseFloat(airport.lat), parseFloat(airport.lng)], { icon: airportIcon })
                    .bindPopup(`<b>${airport.name}</b>`);
                airportsCluster.addLayer(marker);
            } else {
                console.error('Invalid latitude or longitude for airport:', airport);
            }
        });
    } else {
        console.error('Invalid airport data format:', airportData);
    }
}

function displayRestaurantInfo(restaurantData) {
    if (restaurantData && Array.isArray(restaurantData.geonames)) {
        restaurantData.geonames.forEach(function(restaurant) {
            if (restaurant.lat && restaurant.lng) {
                var marker = L.marker([parseFloat(restaurant.lat), parseFloat(restaurant.lng)], { icon: restaurantIcon })
                    .bindPopup(`<b>${restaurant.name}</b>`);
                restaurantCluster.addLayer(marker);
            } else {
                console.error('Invalid latitude or longitude for restaurant:', restaurant);
            }
        });
    } else {
        console.error('Invalid restaurant data format:', restaurantData);
    }
}

function displayMuseumInfo(museumData) {
    if (museumData && Array.isArray(museumData.geonames)) {
        museumData.geonames.forEach(function(museum) {
            if (museum.lat && museum.lng) {
                var marker = L.marker([parseFloat(museum.lat), parseFloat(museum.lng)], { icon: museumIcon })
                    .bindPopup(`<b>${museum.name}</b>`);
                museumsCluster.addLayer(marker);
            } else {
                console.error('Invalid latitude or longitude for museum:', museum);
            }
        });
    } else {
        console.error('Invalid museum data format:', museumData);
    }
}

$('#toggleAirports, #toggleRestaurant, #toggleMuseums').on('change', function() {
    var checkboxId = $(this).attr('id');
    var markerCluster = (checkboxId === 'toggleAirports') ? airportsCluster :
                        (checkboxId === 'toggleRestaurant') ? restaurantCluster : museumsCluster;
    toggleLayer(markerCluster, $(this).prop('checked'));
});

function toggleLayer(layer, checked) {
    if (checked) {
        map.addLayer(layer);
    } else {
        map.removeLayer(layer);
    }
}

var airportIcon = L.icon({ iconUrl: 'icons/airport.png', iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -16] });
var restaurantIcon = L.icon({ iconUrl: 'icons/restaurant.png', iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -16] });
var museumIcon = L.icon({ iconUrl: 'icons/museum.png', iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -16] });

map.on('overlayadd', function(eventLayer) {
    var layerName = eventLayer.name;
    if (layerName === 'Airports') {
        toggleLayer(airportsCluster, true);
    } else if (layerName === 'Restaurant') {
        toggleLayer(restaurantCluster, true);
    } else if (layerName === 'Museums') {
        toggleLayer(museumsCluster, true);
    }
});

map.on('overlayremove', function(eventLayer) {
    var layerName = eventLayer.name;
    if (layerName === 'Airports') {
        toggleLayer(airportsCluster, false);
    } else if (layerName === 'Restaurant') {
        toggleLayer(restaurantCluster, false);
    } else if (layerName === 'Museums') {
        toggleLayer(museumsCluster, false);
    }
});

//BUTTONS

var countryInfoBtn = L.easyButton({
    states: [{
        stateName: 'show-country-info',
        icon: '<i class="fa-solid fa-globe"></i>',
        title: 'Country Info',
        onClick: function(btn, map) {
            $("#countryInfoResults").modal("show");
        }
    }]
});

var weatherInfoBtn = L.easyButton({
    states: [{
        stateName: 'show-weather-info',
        icon: '<i class="fa-solid fa-cloud"></i>',
        title: 'Weather Info',
        onClick: function(btn, map) {
            $("#weatherInfoResults").modal("show");
        }
    }]
});

var currencyInfoBtn = L.easyButton({
    states: [{
        stateName: 'show-currency-info',
        icon: '<i class="fa-solid fa-money-bill-wave"></i>',
        title: 'Currency Info',
        onClick: function(btn, map) {
            $("#currencyInfoResults").modal("show");
        }
    }]
});

var wikipediaInfoBtn = L.easyButton({
    states: [{
        stateName: 'show-wikipedia-info',
        icon: '<i class="fa-brands fa-wikipedia-w"></i>',
        title: 'Wikipedia Info',
        onClick: function(btn, map) {
            $("#wikipediaInfoResults").modal("show");
        }
    }]
});

var newsInfoBtn = L.easyButton({
    states: [{
        stateName: 'show-news-info',
        icon: '<i class="fa-regular fa-newspaper"></i>',
        title: 'News Info',
        onClick: function(btn, map) {
            $("#newsInfoResults").modal("show");
        }
    }]
});


countryInfoBtn.addTo(map);
weatherInfoBtn.addTo(map);
currencyInfoBtn.addTo(map);
wikipediaInfoBtn.addTo(map);
newsInfoBtn.addTo(map);

