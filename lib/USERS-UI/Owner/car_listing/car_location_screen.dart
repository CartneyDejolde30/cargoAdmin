import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';

import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'upload_documents_screen.dart';

class CarLocationScreen extends StatefulWidget {
  final CarListing listing;
   final String vehicleType;

  const CarLocationScreen({super.key, required this.listing, this.vehicleType = 'car',});

  @override
  State<CarLocationScreen> createState() => _CarLocationScreenState();
}

class _CarLocationScreenState extends State<CarLocationScreen> {
  final _locationController = TextEditingController();
  final MapController _mapController = MapController();

  bool _showMap = false;
  bool _isLoadingLocation = false;

  // Default location
  LatLng _currentPosition = LatLng(14.5995, 120.9842);
  List<Marker> _markers = [];

  final String _mapTilerApiKey = 'YGJxmPnRtlTHI1endzDH';

  @override
  void initState() {
    super.initState();

    if (widget.listing.location != null) {
      _locationController.text = widget.listing.location!;
    }

    if (widget.listing.latitude != null && widget.listing.longitude != null) {
      _currentPosition = LatLng(widget.listing.latitude!, widget.listing.longitude!);
    }

    _addMarker(_currentPosition);
    _requestLocationPermission();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (widget.listing.latitude != null && widget.listing.longitude != null) {
        _mapController.move(_currentPosition, 15);
      }
    });
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
        widget.listing.latitude = pos.latitude;
        widget.listing.longitude = pos.longitude;
        _currentPosition = newPos;
        _addMarker(newPos);
        _showMap = true;
      });

      _mapController.move(newPos, 15);

    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text("Unable to get location: $e")),
      );
    }

    setState(() => _isLoadingLocation = false);
  }

  Future<void> _searchAddress(String address) async {
    if (address.trim().isEmpty) return;

    try {
      var locations = await locationFromAddress(address);

      if (locations.isNotEmpty) {
        var loc = locations.first;
        LatLng newPos = LatLng(loc.latitude, loc.longitude);

        setState(() {
          widget.listing.latitude = loc.latitude;
          widget.listing.longitude = loc.longitude;
          widget.listing.location = address;
          _currentPosition = newPos;
          _addMarker(newPos);
          _showMap = true;
        });

        _mapController.move(newPos, 15);
      }
    } catch (_) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Address not found. Try again.")),
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
          _locationController.text = formatted;
          widget.listing.location = formatted;
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
        child: const Icon(Icons.location_pin, color: Colors.red, size: 40),
      ),
    ];
  }

  bool get _canContinue {
    return widget.listing.location != null &&
        widget.listing.location!.trim().isNotEmpty &&
        widget.listing.latitude != null &&
        widget.listing.longitude != null;
  }

void _continue() {
  if (!_canContinue) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text("Please select a valid location before continuing.")),
    );
    return;
  }

  Navigator.push(
    context,
    MaterialPageRoute(
      builder: (_) => UploadDocumentsScreen(
        listing: widget.listing,
        vehicleType: widget.vehicleType, // ADD THIS LINE
      ),
    ),
  );
}

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text("Location", style: GoogleFonts.poppins(fontWeight: FontWeight.w600)),
        centerTitle: true,
      ),

      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text("Your Location",
                        style: GoogleFonts.poppins(fontSize: 20, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 10),

                    Text("Set pickup and return location.",
                        style: GoogleFonts.poppins(color: Colors.black87)),

                    const SizedBox(height: 20),

                    TextField(
                      controller: _locationController,
                      onChanged: (v) {
                        widget.listing.location = v;
                        setState(() {});
                      },
                      decoration: InputDecoration(
                        prefixIcon: const Icon(Icons.location_on, color: Colors.black),
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.search, color: Colors.black),
                          onPressed: () => _searchAddress(_locationController.text),
                        ),
                        filled: true,
                        fillColor: Colors.grey[100],
                        hintText: "Enter complete address",
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),

                    const SizedBox(height: 15),

                    GestureDetector(
                      onTap: () => setState(() => _showMap = !_showMap),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text("Pin on Map", style: GoogleFonts.poppins(fontSize: 14)),
                          Icon(_showMap ? Icons.expand_less : Icons.expand_more, color: Colors.black)
                        ],
                      ),
                    ),

                    if (_showMap) ...[
                      const SizedBox(height: 20),

                      SizedBox(
                        height: 300,
                        child: FlutterMap(
                          mapController: _mapController,
                          options: MapOptions(
                            initialCenter: _currentPosition,
                            initialZoom: 15,
                            onTap: (tapPosition, point) async {
                              setState(() {
                                _currentPosition = point;
                                widget.listing.latitude = point.latitude;
                                widget.listing.longitude = point.longitude;
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
                            MarkerLayer(markers: _markers),
                          ],
                        ),
                      ),

                      const SizedBox(height: 15),

                      ElevatedButton.icon(
                        onPressed: _isLoadingLocation ? null : _getCurrentLocation,
                        icon: _isLoadingLocation
                            ? const CircularProgressIndicator(color: Colors.white, strokeWidth: 2)
                            : const Icon(Icons.my_location),
                        label: Text(_isLoadingLocation ? "Locating..." : "Use Current Location"),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.black),
                      ),
                    ],
                  ],
                ),
              ),
            ),

            Padding(
              padding: const EdgeInsets.all(24),
              child: ElevatedButton(
                onPressed: _canContinue ? _continue : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.black,
                  disabledBackgroundColor: Colors.grey,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
child: Text(
  "Continue",
  style: GoogleFonts.poppins(
    color: _canContinue ? Colors.white : Colors.grey[400] as Color,
    fontWeight: FontWeight.w600,
  ),
),

              ),
            ),
          ],
        ),
      ),
    );
  }
}
