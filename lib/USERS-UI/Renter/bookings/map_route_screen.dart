import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class MapRouteScreen extends StatefulWidget {
  final double destinationLat;
  final double destinationLng;
  final String locationName;
  final String carName;

  const MapRouteScreen({
    super.key,
    required this.destinationLat,
    required this.destinationLng,
    required this.locationName,
    required this.carName,
  });

  @override
  State<MapRouteScreen> createState() => _MapRouteScreenState();
}

class _MapRouteScreenState extends State<MapRouteScreen> {
  static const String _mapTilerKey = 'YGJxmPnRtlTHI1endzDH';
  
  final MapController _mapController = MapController();
  Position? _currentPosition;
  List<LatLng> _routeCoordinates = [];
  String? _distance;
  String? _duration;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _initializeLocation();
  }

  Future<void> _initializeLocation() async {
    await _getCurrentLocation();
    if (_currentPosition != null) {
      await _fetchRoute();
    }
  }

  Future<void> _getCurrentLocation() async {
    try {
      final permission = await _checkLocationPermission();
      if (!permission) return;

      setState(() => _isLoading = true);

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      if (mounted) {
        setState(() => _currentPosition = position);
      }
    } catch (e) {
      _showErrorSnackBar('Failed to get location: ${e.toString()}');
    }
  }

  Future<bool> _checkLocationPermission() async {
    LocationPermission permission = await Geolocator.checkPermission();
    
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (permission == LocationPermission.deniedForever) {
      _showErrorSnackBar('Location permission permanently denied. Please enable in settings.');
      return false;
    }

    if (permission == LocationPermission.denied) {
      _showErrorSnackBar('Location permission denied');
      return false;
    }

    return true;
  }

  // Using OSRM (Open Source Routing Machine) - FREE routing service
  Future<void> _fetchRoute() async {
    if (_currentPosition == null) return;

    setState(() => _isLoading = true);

    try {
      // OSRM API - Free routing service
      // Format: /route/v1/{profile}/{coordinates}
      final url = Uri.parse(
        'https://router.project-osrm.org/route/v1/driving/'
        '${_currentPosition!.longitude},${_currentPosition!.latitude};'
        '${widget.destinationLng},${widget.destinationLat}'
        '?overview=full&geometries=geojson'
      );

      print('Fetching route from: $url');

      final response = await http.get(url).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw Exception('Request timeout');
        },
      );

      print('Response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        _parseRouteResponse(response.body);
      } else {
        throw Exception('API error: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      print('Route fetch error: $e');
      _showErrorSnackBar('Failed to load route: ${e.toString()}');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _parseRouteResponse(String responseBody) {
    try {
      final data = json.decode(responseBody);
      
      print('Parsed data keys: ${data.keys}');

      if (data['code'] != 'Ok') {
        _showErrorSnackBar('Route not found: ${data['message'] ?? 'Unknown error'}');
        return;
      }

      if (data['routes'] == null || (data['routes'] as List).isEmpty) {
        _showErrorSnackBar('No route found');
        return;
      }

      final route = data['routes'][0];
      
      // Extract geometry (GeoJSON format)
      if (route['geometry'] != null) {
        final geometry = route['geometry'];
        
        if (geometry['coordinates'] != null) {
          final coords = geometry['coordinates'] as List;
          setState(() {
            _routeCoordinates = coords
                .map((coord) => LatLng(coord[1] as double, coord[0] as double))
                .toList();
          });
          print('Route coordinates extracted: ${_routeCoordinates.length} points');
        }
      }

      // Extract distance (already in meters)
      if (route['distance'] != null) {
        final distanceMeters = (route['distance'] as num).toDouble();
        final km = (distanceMeters / 1000).toStringAsFixed(1);
        setState(() {
          _distance = '$km km';
        });
      }

      // Extract duration (already in seconds)
      if (route['duration'] != null) {
        final durationSeconds = (route['duration'] as num).toDouble();
        final minutes = (durationSeconds / 60).round();
        setState(() {
          _duration = '$minutes min';
        });
      }

      // Fit map after data is loaded
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _fitMapToRoute();
      });
    } catch (e) {
      print('Parse error: $e');
      _showErrorSnackBar('Failed to parse route data');
    }
  }

  void _fitMapToRoute() {
    if (_currentPosition == null || _routeCoordinates.isEmpty) {
      print('Cannot fit map: position or coordinates missing');
      return;
    }

    final allPoints = [
      LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
      ..._routeCoordinates,
    ];

    double minLat = allPoints.first.latitude;
    double maxLat = allPoints.first.latitude;
    double minLng = allPoints.first.longitude;
    double maxLng = allPoints.first.longitude;

    for (final point in allPoints) {
      if (point.latitude < minLat) minLat = point.latitude;
      if (point.latitude > maxLat) maxLat = point.latitude;
      if (point.longitude < minLng) minLng = point.longitude;
      if (point.longitude > maxLng) maxLng = point.longitude;
    }

    const padding = 0.01;
    final bounds = LatLngBounds(
      LatLng(minLat - padding, minLng - padding),
      LatLng(maxLat + padding, maxLng + padding),
    );

    try {
      _mapController.fitCamera(
        CameraFit.bounds(
          bounds: bounds,
          padding: const EdgeInsets.all(50),
        ),
      );
      print('Map fitted to route');
    } catch (e) {
      print('Error fitting map: $e');
    }
  }

  void _showErrorSnackBar(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.all(16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        duration: const Duration(seconds: 4),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          _buildMap(),
          _buildTopBar(),
          if (_isLoading) _buildLoadingIndicator(),
          if (_currentPosition != null && !_isLoading) _buildBottomCard(),
        ],
      ),
    );
  }

  Widget _buildMap() {
    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: LatLng(widget.destinationLat, widget.destinationLng),
        initialZoom: 12.0,
        minZoom: 5.0,
        maxZoom: 18.0,
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=$_mapTilerKey',
          userAgentPackageName: 'com.yourcompany.app',
        ),
        if (_routeCoordinates.isNotEmpty) _buildRouteLine(),
        _buildMarkers(),
      ],
    );
  }

  Widget _buildRouteLine() {
    return PolylineLayer(
      polylines: [
        Polyline(
          points: _routeCoordinates,
          color: Colors.blue.shade700,
          strokeWidth: 5.0,
          borderColor: Colors.white,
          borderStrokeWidth: 2.0,
        ),
      ],
    );
  }

  Widget _buildMarkers() {
    return MarkerLayer(
      markers: [
        if (_currentPosition != null)
          _buildMarker(
            LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
            Colors.green.shade600,
            Icons.my_location,
            'Your Location',
          ),
        _buildMarker(
          LatLng(widget.destinationLat, widget.destinationLng),
          Colors.red.shade600,
          Icons.directions_car,
          'Car Location',
        ),
      ],
    );
  }

  Marker _buildMarker(LatLng position, Color color, IconData icon, String label) {
    return Marker(
      point: position,
      width: 80,
      height: 80,
      child: Column(
        children: [
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 3),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.3),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Icon(icon, color: Colors.white, size: 24),
          ),
        ],
      ),
    );
  }

  Widget _buildTopBar() {
    return Positioned(
      top: 0,
      left: 0,
      right: 0,
      child: Container(
        padding: EdgeInsets.only(
          top: MediaQuery.of(context).padding.top + 8,
          bottom: 16,
          left: 16,
          right: 16,
        ),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Colors.black.withValues(alpha: 0.7),
              Colors.transparent,
            ],
          ),
        ),
        child: Row(
          children: [
            IconButton(
              onPressed: () => Navigator.pop(context),
              icon: const Icon(Icons.arrow_back, color: Colors.white),
              style: IconButton.styleFrom(
                backgroundColor: Colors.black.withValues(alpha: 0.5),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Route to ${widget.carName}',
                    style: GoogleFonts.poppins(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    widget.locationName,
                    style: GoogleFonts.poppins(
                      color: Colors.white.withValues(alpha: 0.9),
                      fontSize: 12,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLoadingIndicator() {
    return Positioned(
      top: MediaQuery.of(context).padding.top + 80,
      left: 0,
      right: 0,
      child: Center(
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.black,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                'Loading route...',
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBottomCard() {
    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Container(
        margin: const EdgeInsets.all(16),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 20,
              offset: const Offset(0, -5),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem(
                    Icons.straighten,
                    'Distance',
                    _distance ?? 'Calculating...',
                    Colors.blue,
                  ),
                ),
                Container(
                  width: 1,
                  height: 40,
                  color: Colors.grey.shade300,
                ),
                Expanded(
                  child: _buildInfoItem(
                    Icons.access_time,
                    'Duration',
                    _duration ?? 'Calculating...',
                    Colors.orange,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _fitMapToRoute,
                    icon: const Icon(Icons.my_location, size: 18),
                    label: Text(
                      'Recenter',
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.black,
                      side: BorderSide(color: Colors.grey.shade300),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, size: 18),
                    label: Text(
                      'Close',
                      style: GoogleFonts.poppins(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.black,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(IconData icon, String label, String value, Color color) {
    return Column(
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: 4),
        Text(
          label,
          style: GoogleFonts.poppins(
            fontSize: 11,
            color: Colors.grey.shade600,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: GoogleFonts.poppins(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.black,
          ),
        ),
      ],
    );
  }
}