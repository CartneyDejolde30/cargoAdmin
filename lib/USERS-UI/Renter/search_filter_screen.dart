import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';

class SearchFilterScreen extends StatefulWidget {
  final Map<String, dynamic>? currentFilters;
  
  const SearchFilterScreen({
    super.key,
    this.currentFilters,
  });

  @override
  State<SearchFilterScreen> createState() => _SearchFilterScreenState();
}

class _SearchFilterScreenState extends State<SearchFilterScreen> {
  final TextEditingController _locationController = TextEditingController();
  String _selectedDeliveryMethod = '';
  String _selectedVehicleType = '';
  RangeValues _priceRange = const RangeValues(0, 2000);
  bool _isLoadingLocation = false;

  @override
  void initState() {
    super.initState();
    // Load existing filters if provided
    if (widget.currentFilters != null) {
      _locationController.text = widget.currentFilters!['location'] ?? '';
      _selectedDeliveryMethod = widget.currentFilters!['deliveryMethod'] ?? '';
      _selectedVehicleType = widget.currentFilters!['vehicleType'] ?? '';
      if (widget.currentFilters!['minPrice'] != null) {
        _priceRange = RangeValues(
          widget.currentFilters!['minPrice'],
          widget.currentFilters!['maxPrice'],
        );
      }
    }
  }

  @override
  void dispose() {
    _locationController.dispose();
    super.dispose();
  }

  Future<void> _useCurrentLocation() async {
    setState(() => _isLoadingLocation = true);

    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        throw Exception('Location permission denied');
      }

      Position position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      List<Placemark> placemarks = await placemarkFromCoordinates(
        position.latitude,
        position.longitude,
      );

      if (placemarks.isNotEmpty) {
        Placemark place = placemarks.first;
        String address = "${place.locality ?? ""}, ${place.administrativeArea ?? ""}".trim();
        
        setState(() {
          _locationController.text = address.isEmpty ? "Current Location" : address;
        });

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Location set to: ${_locationController.text}'),
              backgroundColor: Colors.black,
              duration: const Duration(seconds: 2),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Unable to get location: ${e.toString()}'),
            backgroundColor: Colors.red.shade900,
          ),
        );
      }
    } finally {
      setState(() => _isLoadingLocation = false);
    }
  }

  void _showVehicleTypeModal() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Select Vehicle Type',
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.black,
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close, color: Colors.black),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildVehicleTypeOption('Car', Icons.directions_car),
            _buildVehicleTypeOption('Motorcycle', Icons.two_wheeler),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildVehicleTypeOption(String type, IconData icon) {
    bool isSelected = _selectedVehicleType == type;
    return InkWell(
      onTap: () {
        setState(() {
          _selectedVehicleType = type;
        });
        Navigator.pop(context);
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
        decoration: BoxDecoration(
          color: isSelected ? Colors.black : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? Colors.black : Colors.grey.shade300,
            width: 1.5,
          ),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              color: isSelected ? Colors.white : Colors.black,
              size: 28,
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                type,
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                  color: isSelected ? Colors.white : Colors.black,
                ),
              ),
            ),
            if (isSelected)
              const Icon(Icons.check_circle, color: Colors.white),
          ],
        ),
      ),
    );
  }

  void _showDeliveryMethodModal() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Select Delivery Method',
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.black,
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close, color: Colors.black),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _buildDeliveryOption('Guest Pick-up and Guest Return'),
            _buildDeliveryOption('Guest Pick-up and Host Collection'),
            _buildDeliveryOption('Host Delivery and Guest Return'),
            _buildDeliveryOption('Host Delivery and Host Collection'),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildDeliveryOption(String option) {
    bool isSelected = _selectedDeliveryMethod == option;
    return InkWell(
      onTap: () {
        setState(() {
          _selectedDeliveryMethod = option;
        });
        Navigator.pop(context);
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          border: Border(
            bottom: BorderSide(color: Colors.grey.shade200),
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                option,
                style: GoogleFonts.poppins(
                  fontSize: 15,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                  color: Colors.black,
                ),
              ),
            ),
            if (isSelected)
              const Icon(Icons.check_circle, color: Colors.black),
          ],
        ),
      ),
    );
  }

  void _showLocationPicker() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const LocationPickerScreen(),
      ),
    ).then((value) {
      if (value != null) {
        setState(() {
          _locationController.text = value;
        });
      }
    });
  }

  void _clearAll() {
    setState(() {
      _locationController.clear();
      _selectedDeliveryMethod = '';
      _selectedVehicleType = '';
      _priceRange = const RangeValues(0, 2000);
    });
  }

  void _search() {
    Map<String, dynamic> searchParams = {
      'location': _locationController.text,
      'deliveryMethod': _selectedDeliveryMethod,
      'vehicleType': _selectedVehicleType,
      'minPrice': _priceRange.start,
      'maxPrice': _priceRange.end,
    };
    
    Navigator.pop(context, searchParams);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 1,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.black,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.arrow_back_ios_new,
              color: Colors.white,
              size: 16,
            ),
          ),
        ),
        title: Text(
          'Search a car',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Location Section
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Pickup Location',
                        style: GoogleFonts.poppins(
                          fontSize: 14,
                          color: Colors.black,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      _isLoadingLocation
                          ? SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.black,
                                ),
                              ),
                            )
                          : TextButton.icon(
                              onPressed: _useCurrentLocation,
                              icon: Icon(
                                Icons.my_location,
                                color: Colors.black,
                                size: 18,
                              ),
                              label: Text(
                                'Cars Near Me',
                                style: GoogleFonts.poppins(
                                  fontSize: 13,
                                  color: Colors.black,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  GestureDetector(
                    onTap: _showLocationPicker,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 16,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.location_on,
                            color: Colors.black,
                            size: 24,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              _locationController.text.isEmpty
                                  ? 'Select location or use current location'
                                  : _locationController.text,
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                color: _locationController.text.isEmpty
                                    ? Colors.grey.shade600
                                    : Colors.black,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Vehicle Type Section
                  Text(
                    'Vehicle Type',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.black,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 12),
                  GestureDetector(
                    onTap: _showVehicleTypeModal,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 16,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Icon(
                                _selectedVehicleType == 'Car' 
                                    ? Icons.directions_car 
                                    : _selectedVehicleType == 'Motorcycle'
                                    ? Icons.two_wheeler
                                    : Icons.category,
                                color: Colors.black,
                                size: 24,
                              ),
                              const SizedBox(width: 12),
                              Text(
                                _selectedVehicleType.isEmpty
                                    ? 'Choose vehicle type'
                                    : _selectedVehicleType,
                                style: GoogleFonts.poppins(
                                  fontSize: 14,
                                  color: _selectedVehicleType.isEmpty
                                      ? Colors.grey.shade600
                                      : Colors.black,
                                ),
                              ),
                            ],
                          ),
                          Icon(
                            Icons.keyboard_arrow_down,
                            color: Colors.black,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Price Range Section
                  Text(
                    'Price Range',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.black,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              '\$${_priceRange.start.round()}',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.black,
                              ),
                            ),
                            Text(
                              '\$${_priceRange.end.round()}',
                              style: GoogleFonts.poppins(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.black,
                              ),
                            ),
                          ],
                        ),
                        RangeSlider(
                          values: _priceRange,
                          min: 0,
                          max: 2000,
                          divisions: 40,
                          activeColor: Colors.black,
                          inactiveColor: Colors.grey.shade300,
                          onChanged: (RangeValues values) {
                            setState(() {
                              _priceRange = values;
                            });
                          },
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Delivery Method Section
                  Text(
                    'Delivery Method',
                    style: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.black,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 12),
                  GestureDetector(
                    onTap: _showDeliveryMethodModal,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 16,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              _selectedDeliveryMethod.isEmpty
                                  ? 'Choose delivery method'
                                  : _selectedDeliveryMethod,
                              style: GoogleFonts.poppins(
                                fontSize: 14,
                                color: _selectedDeliveryMethod.isEmpty
                                    ? Colors.grey.shade600
                                    : Colors.black,
                              ),
                            ),
                          ),
                          Icon(
                            Icons.keyboard_arrow_down,
                            color: Colors.black,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Bottom Buttons
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(
                top: BorderSide(color: Colors.grey.shade200),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _clearAll,
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      side: BorderSide(color: Colors.black, width: 1.5),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      'Clear All',
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Colors.black,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: _search,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.black,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      elevation: 0,
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.search,
                          color: Colors.white,
                          size: 20,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'Search',
                          style: GoogleFonts.poppins(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// Location Picker Screen (continued with black & white theme)
class LocationPickerScreen extends StatefulWidget {
  const LocationPickerScreen({super.key});

  @override
  State<LocationPickerScreen> createState() => _LocationPickerScreenState();
}

class _LocationPickerScreenState extends State<LocationPickerScreen> {
  final TextEditingController _searchController = TextEditingController();
  final MapController _mapController = MapController();
  
  double _radius = 50.0;
  bool _isLoadingLocation = false;
  bool _showMunicipalitiesList = false;
  
  LatLng _currentPosition = LatLng(14.5995, 120.9842);
  List<Marker> _markers = [];
  List<CircleMarker> _circles = [];
  
  final String _mapTilerApiKey = 'YGJxmPnRtlTHI1endzDH';
  String _selectedAddress = '';
  
  final Map<String, LatLng> _agusanMunicipalities = {
    'Bayugan': LatLng(8.7167, 125.7500),
    'Bunawan': LatLng(8.1667, 125.9667),
    'Esperanza': LatLng(8.6167, 125.6500),
    'La Paz': LatLng(8.7000, 125.8833),
    'Loreto': LatLng(8.5833, 125.5167),
    'Prosperidad': LatLng(8.6000, 125.9167),
    'Rosario': LatLng(8.7833, 125.9000),
    'San Francisco': LatLng(8.5167, 125.9667),
    'San Luis': LatLng(8.4667, 125.7667),
    'Santa Josefa': LatLng(8.9333, 125.9667),
    'Sibagat': LatLng(8.9500, 125.7333),
    'Talacogon': LatLng(8.8667, 125.7833),
    'Trento': LatLng(8.0500, 126.0667),
    'Veruela': LatLng(8.1167, 125.9333),
  };

  @override
  void initState() {
    super.initState();
    _addMarker(_currentPosition);
    _requestLocationPermission();
    _getCurrentLocation();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _requestLocationPermission() async {
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      await Geolocator.requestPermission();
    }
  }

  Future<void> _getCurrentLocation() async {
    setState(() => _isLoadingLocation = true);
    try {
      Position pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );
      LatLng newPos = LatLng(pos.latitude, pos.longitude);
      await _updateAddressFromCoordinates(newPos);
      setState(() {
        _currentPosition = newPos;
        _addMarker(newPos);
      });
      _mapController.move(newPos, 15);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Unable to get location: $e")),
        );
      }
    }
    setState(() => _isLoadingLocation = false);
  }

  Future<void> _searchAddress(String address) async {
    if (address.trim().isEmpty) return;
    setState(() => _showMunicipalitiesList = false);
    try {
      var locations = await locationFromAddress(address);
      if (locations.isNotEmpty) {
        var loc = locations.first;
        LatLng newPos = LatLng(loc.latitude, loc.longitude);
        setState(() {
          _currentPosition = newPos;
          _selectedAddress = address;
          _searchController.text = address;
          _addMarker(newPos);
        });
        _mapController.move(newPos, 15);
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Address not found. Try again.")),
        );
      }
    }
  }
  
  void _selectMunicipality(String municipality) {
    if (_agusanMunicipalities.containsKey(municipality)) {
      LatLng coordinates = _agusanMunicipalities[municipality]!;
      String fullAddress = '$municipality, Agusan del Sur, Philippines';
      setState(() {
        _showMunicipalitiesList = false;
        _currentPosition = coordinates;
        _selectedAddress = fullAddress;
        _searchController.text = fullAddress;
        _addMarker(coordinates);
      });
      _mapController.move(coordinates, 13);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Location set to $municipality'),
          backgroundColor: Colors.black,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  Future<void> _updateAddressFromCoordinates(LatLng pos) async {
    try {
      var places = await placemarkFromCoordinates(pos.latitude, pos.longitude);
      if (places.isNotEmpty) {
        var p = places.first;
        String formatted =
            "${p.street ?? ""}, ${p.locality ?? ""}, ${p.administrativeArea ?? ""}".trim();
        setState(() {
          _searchController.text = formatted;
          _selectedAddress = formatted;
        });
      }
    } catch (_) {}
  }

  void _addMarker(LatLng pos) {
    _markers = [
      Marker(
        point: pos,
        width: 40,
        height: 40,
        child: const Icon(Icons.location_pin, color: Colors.black, size: 40),
      ),
    ];
    _updateCircle(pos);
  }

  void _updateCircle(LatLng pos) {
    _circles = [
      CircleMarker(
        point: pos,
        radius: _radius * 1000,
        color: Colors.black.withAlpha((0.05 * 255).round()),
        borderColor: Colors.black,
        borderStrokeWidth: 2,
        useRadiusInMeter: true,
      ),
    ];
  }

  void _continue() {
    if (_selectedAddress.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Please select a location")),
      );
      return;
    }
    Navigator.pop(context, _selectedAddress);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 1,
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.black,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.arrow_back_ios_new,
              color: Colors.white,
              size: 16,
            ),
          ),
        ),
        title: Text(
          'Where do you want to go?',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: _searchController,
                  readOnly: true,
                  onTap: () {
                    setState(() {
                      _showMunicipalitiesList = !_showMunicipalitiesList;
                    });
                  },
                  decoration: InputDecoration(
                    prefixIcon: Icon(
                      Icons.location_on,
                      color: Colors.black,
                    ),
                    suffixIcon: _showMunicipalitiesList
                        ? IconButton(
                            icon: Icon(Icons.close, color: Colors.black),
                            onPressed: () {
                              setState(() => _showMunicipalitiesList = false);
                            },
                          )
                        : IconButton(
                            icon: Icon(Icons.search, color: Colors.black),
                            onPressed: () {
                              setState(() => _showMunicipalitiesList = false);
                              _searchAddress(_searchController.text);
                            },
                          ),
                    filled: true,
                    fillColor: Colors.grey.shade100,
                    hintText: 'Select Location',
                    hintStyle: GoogleFonts.poppins(
                      fontSize: 14,
                      color: Colors.grey.shade600,
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                  ),
                ),
                
                if (_showMunicipalitiesList)
                  Container(
                    margin: const EdgeInsets.only(top: 8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withAlpha((0.1 * 255).round()),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    constraints: const BoxConstraints(maxHeight: 250),
                    child: Material(
                      color: Colors.transparent,
                      child: ListView.separated(
                        shrinkWrap: true,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _agusanMunicipalities.length,
                        separatorBuilder: (context, index) => Divider(
                          height: 1,
                          color: Colors.grey.shade200,
                        ),
                        itemBuilder: (context, index) {
                          final municipality = _agusanMunicipalities.keys.elementAt(index);
                          return InkWell(
                            onTap: () => _selectMunicipality(municipality),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 12,
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.location_city,
                                    color: Colors.black,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          municipality,
                                          style: GoogleFonts.poppins(
                                            fontSize: 14,
                                            fontWeight: FontWeight.w500,
                                            color: Colors.black,
                                          ),
                                        ),
                                        Text(
                                          'Agusan del Sur',
                                          style: GoogleFonts.poppins(
                                            fontSize: 12,
                                            color: Colors.grey.shade600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ),
                
                const SizedBox(height: 20),
                Text(
                  'Radius',
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.black,
                  ),
                ),
                Slider(
                  value: _radius,
                  min: 0,
                  max: 100,
                  divisions: 20,
                  label: '${_radius.round()} km',
                  activeColor: Colors.black,
                  inactiveColor: Colors.grey.shade300,
                  onChanged: (value) {
                    setState(() {
                      _radius = value;
                      _updateCircle(_currentPosition);
                    });
                  },
                ),
                Text(
                  '${_radius.round()} km radius',
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: Stack(
              children: [
                FlutterMap(
                  mapController: _mapController,
                  options: MapOptions(
                    initialCenter: _currentPosition,
                    initialZoom: 15,
                    onTap: (tapPosition, point) async {
                      setState(() {
                        _currentPosition = point;
                        _addMarker(point);
                      });
                      await _updateAddressFromCoordinates(point);
                    },
                  ),
                  children: [
                    TileLayer(
                      urlTemplate:
                          'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=$_mapTilerApiKey',
                    ),
                    CircleLayer(circles: _circles),
                    MarkerLayer(markers: _markers),
                  ],
                ),
                Positioned(
                  bottom: 16,
                  right: 16,
                  child: FloatingActionButton(
                    onPressed: _isLoadingLocation ? null : _getCurrentLocation,
                    backgroundColor: Colors.black,
                    child: _isLoadingLocation
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Icon(
                            Icons.my_location,
                            color: Colors.white,
                          ),
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(
                top: BorderSide(color: Colors.grey.shade200),
              ),
            ),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _continue,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
                ),
                child: Text(
                  'Continue',
                  style: GoogleFonts.poppins(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}