import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_application_1/USERS-UI/Owner/models/car_listing.dart';
import 'car_preferences_screen.dart';

class CarDetailsScreen extends StatefulWidget {
  final CarListing? existingListing;
  final int ownerId;
  final String vehicleType; 

  const CarDetailsScreen({
    super.key,
    this.existingListing,
    required this.ownerId,
    this.vehicleType = 'car'
  });

  @override
  State<CarDetailsScreen> createState() => _CarDetailsScreenState();
}

class _CarDetailsScreenState extends State<CarDetailsScreen> {
  late CarListing listing;
  final _plateController = TextEditingController();
  final _colorController = TextEditingController();
  bool _plateIsUnique = false;

  File? imageFile;
  Uint8List? webImage;

  final List<String> years = List.generate(10, (i) => (2025 - i).toString());
  final List<String> brands = [
    'Toyota',
    'Honda',
    'Mitsubishi',
    'Nissan',
    'Mazda',
    'Suzuki',
    'Hyundai',
    'Kia',
    'Ford',
    'Chevrolet',
    'Isuzu',
    'Subaru',
    'BMW',
    'Mercedes-Benz',
    'Audi',
    'Lexus',
    'Volkswagen',
    'Geely',
    'Chery',
    'MG',
    'BYD',
  ];

  final Map<String, List<String>> modelsByBrand = {
    'Toyota': ['Vios', 'Corolla Altis', 'Camry', 'Fortuner', 'Innova', 'Wigo', 'Rush', 'Raize', 'Hilux', 'Land Cruiser', 'Prado', 'RAV4', 'Avanza', 'Veloz'],
    'Honda': ['City', 'Civic', 'Accord', 'CR-V', 'HR-V', 'BR-V', 'Brio', 'Jazz', 'Mobilio', 'Odyssey'],
    'Mitsubishi': ['Mirage', 'Mirage G4', 'Montero Sport', 'Xpander', 'Strada', 'L300', 'Pajero', 'ASX', 'Outlander PHEV'],
    'Nissan': ['Almera', 'Sylphy', 'Patrol', 'Terra', 'Navara', 'Urvan', 'Juke', 'Kicks', 'X-Trail', 'Leaf'],
    'Mazda': ['Mazda2', 'Mazda3', 'Mazda6', 'CX-3', 'CX-5', 'CX-9', 'CX-30', 'CX-60', 'BT-50', 'MX-5'],
    'Suzuki': ['Swift', 'Dzire', 'Celerio', 'Ertiga', 'S-Presso', 'Vitara', 'XL7', 'Jimny', 'APV', 'Ciaz'],
    'Hyundai': ['Accent', 'Elantra', 'Reina', 'Tucson', 'Santa Fe', 'Creta', 'Kona', 'Stargazer', 'Staria', 'Palisade'],
    'Kia': ['Picanto', 'Soluto', 'Stonic', 'Seltos', 'Sportage', 'Sorento', 'Carnival', 'EV6', 'Forte', 'K2500/K3000'],
    'Ford': ['EcoSport', 'Territory', 'Everest', 'Ranger', 'Expedition', 'Explorer', 'Mustang', 'F-150'],
    'Chevrolet': ['Spark', 'Sail', 'Malibu', 'Trailblazer', 'Tracker', 'Colorado', 'Suburban', 'Corvette'],
    'Isuzu': ['D-Max', 'mu-X', 'Traviz', 'N-Series (Trucks)'],
    'Subaru': ['Impreza', 'XV', 'Forester', 'Outback', 'Levorg', 'WRX', 'BRZ'],
    'BMW': ['2 Series', '3 Series', '5 Series', '7 Series', 'X1', 'X3', 'X5', 'X7', 'iX', 'i4'],
    'Mercedes-Benz': ['A-Class', 'C-Class', 'E-Class', 'S-Class', 'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'EQB', 'EQE'],
    'Audi': ['A3', 'A4', 'A6', 'Q2', 'Q3', 'Q5', 'Q7', 'Q8', 'e-tron'],
    'Lexus': ['IS', 'ES', 'LS', 'UX', 'NX', 'RX', 'LX', 'LM'],
    'Volkswagen': ['Santana', 'Lamando', 'Tiguan', 'T-Cross'],
    'Geely': ['Coolray', 'Azkarra', 'Okavango', 'Emgrand'],
    'Chery': ['Tiggo 7 Pro', 'Tiggo 8 Pro', 'Tiggo 5X'],
    'MG': ['MG5', 'MG6', 'ZS', 'RX5', 'HS'],
    'BYD': ['Atto 3', 'Dolphin', 'Seal', 'Han'],
  };

  final List<String> bodyStyles = [
    'Sedan',
    'Hatchback',
    'SUV',
    'Crossover',
    'MPV/Van',
    'Pickup Truck',
    'Coupe',
    'Wagon',
  ];

  final List<String> motorcycleBrands = [
    'Honda',
    'Yamaha',
    'Suzuki',
    'Kawasaki',
    'Rusi',
    'TVS',
    'KTM',
    'Royal Enfield',
    'Keeway',
    'Benelli',
    'CFMoto',
    'Kymco',
    'SYM',
    'Vespa',
    'Moto Morini',
  ];

  final Map<String, List<String>> motorcycleModelsByBrand = {
    'Honda': [
      'Click 125i', 'Click 160', 'Beat', 'ADV 160', 'PCX 160',
      'Air Blade 160', 'Wave 110', 'TMX 125', 'XRM 125',
      'CB150R', 'CBR150R', 'CRF150L', 'CRF300L', 'CB190R',
      'CBR1000RR', 'CB1000R', 'CB650R', 'Rebel 500', 'CB500X'
    ],
    'Yamaha': [
      'Mio i 125', 'Mio Aerox', 'Mio Gear', 'NMAX', 'Sniper 155',
      'YZF-R15', 'MT-15', 'XSR155', 'FZ150i', 'Sight 115',
      'XTZ125', 'WR155R', 'YZF-R3', 'MT-03', 'MT-07', 'MT-09',
      'YZF-R1', 'TMAX 560', 'Tenere 700'
    ],
    'Suzuki': [
      'Smash 115', 'Raider R150', 'Skydrive', 'Burgman Street',
      'Gixxer 150', 'Gixxer SF 250', 'GSX-R150', 'GSX-S150',
      'V-Strom 250', 'GSX-R1000', 'GSX-S1000', 'Hayabusa',
      'Katana', 'SV650'
    ],
    'Kawasaki': [
      'CT 100', 'Rouser NS 160', 'Rouser RS 200', 'Bajaj Dominar 400',
      'Ninja 400', 'Z400', 'Versys 650', 'Ninja 650', 'Z650',
      'Vulcan S', 'Ninja 1000SX', 'Z900', 'Z H2', 'Ninja ZX-10R'
    ],
    'Rusi': [
      'Mojo 125', 'Cyclone 125', 'Viper 125', 'Classic 150',
      'Adventure 150'
    ],
    'TVS': [
      'Dazz', 'Raider 125', 'Neo NX', 'XL100', 'Apache RTR 160',
      'Apache RTR 200', 'NTORQ 125'
    ],
    'KTM': [
      'Duke 125', 'RC 125', 'Duke 200', 'RC 200', 'Duke 390',
      'RC 390', '390 Adventure', 'Duke 790', '890 Duke R'
    ],
    'Royal Enfield': [
      'Classic 350', 'Meteor 350', 'Hunter 350', 'Bullet 350',
      'Himalayan', 'Interceptor 650', 'Continental GT 650'
    ],
    'Keeway': [
      'RKS 100', 'K-Light 125', 'K-Light 202', 'Sixties 300i',
      'Vieste 300', 'Café Racer 152', 'Superlight 200'
    ],
    'Benelli': [
      'TNT 135', '155S', 'TNT 249S', 'Leoncino 250', 'TRK 251',
      '302R', '502C', 'Leoncino 500', 'TRK 502', 'TNT 600i'
    ],
    'CFMoto': [
      '150NK', '300NK', '300SR', '400NK', '650MT', '650GT',
      '700CL-X'
    ],
    'Kymco': [
      'Like 150i', 'X-Town 300i', 'AK 550', 'Xciting 400i'
    ],
    'SYM': [
      'Jet 14', 'Bonus 110', 'Symphony SR', 'Mio 115', 'VF3i 185'
    ],
    'Vespa': [
      'Primavera 150', 'Sprint 150', 'GTS 300', 'GTS Super 300'
    ],
    'Moto Morini': [
      'X-Cape 650', 'Seiemmezzo 650'
    ],
  };

  final List<String> motorcycleBodyStyles = [
    'Scooter',
    'Underbone',
    'Sport',
    'Standard/Naked',
    'Dual Sport/Adventure',
    'Cruiser',
    'Café Racer',
    'Touring',
  ];

  final List<String> engineDisplacements = [
    '100-125cc',
    '126-150cc',
    '151-200cc',
    '201-300cc',
    '301-400cc',
    '401-650cc',
    '651cc+',
  ];

  final List<String> transmissionTypes = [
    'Manual',
    'Automatic',
    'Semi-Automatic',
  ];

  // 🔧 ADD THIS HELPER METHOD
  String? _getValidValue(String? currentValue, List<String> validValues) {
    if (currentValue == null || currentValue.isEmpty) return null;
    
    // If the current value exists in valid values, return it
    if (validValues.contains(currentValue)) {
      return currentValue;
    }
    
    // Try to map old values to new ones
    final lowerValue = currentValue.toLowerCase();
    
    // Map transmission types
    if (lowerValue.contains('auto') || lowerValue == 'a1' || lowerValue == 'at') {
      return 'Automatic';
    }
    if (lowerValue.contains('manual') || lowerValue == 'm1' || lowerValue == 'mt') {
      return 'Manual';
    }
    if (lowerValue.contains('semi')) {
      return 'Semi-Automatic';
    }
    
    // If no mapping found, return null (will show hint text)
    return null;
  }

@override
void initState() {
  super.initState();

  listing = widget.existingListing ??
      CarListing(
        owner: widget.ownerId,
        carStatus: 'Pending',
      );

  listing.photoUrls = List<String>.from(listing.photoUrls);

  final isMotorcycle = widget.vehicleType == 'motorcycle';
  
  // 🔧 FIX: Validate all dropdown values
  listing.year = _getValidValue(listing.year, years) ?? years[0];
  
  final brandsList = isMotorcycle ? motorcycleBrands : brands;
  listing.brand = _getValidValue(listing.brand, brandsList) ?? brandsList[0];
  
  final bodyStylesList = isMotorcycle ? motorcycleBodyStyles : bodyStyles;
  listing.bodyStyle = _getValidValue(listing.bodyStyle, bodyStylesList) ?? bodyStylesList[0];
  
  final trimsList = isMotorcycle ? engineDisplacements : ['N/A', 'Base', 'Sport', 'Luxury'];
  listing.trim = _getValidValue(listing.trim, trimsList) ?? (isMotorcycle ? engineDisplacements[0] : 'N/A');

  if (listing.brand != null) {
    final models = isMotorcycle 
        ? (motorcycleModelsByBrand[listing.brand!] ?? [])
        : (modelsByBrand[listing.brand!] ?? []);
    
    // Validate model as well
    listing.model = _getValidValue(listing.model, models) ?? (models.isNotEmpty ? models[0] : null);
  }

  _plateController.text = listing.plateNumber ?? '';
  _colorController.text = listing.color ?? '';
  _plateIsUnique = _plateController.text.isNotEmpty;

  _plateController.addListener(() {
    setState(() {
      listing.plateNumber = _plateController.text;
      _plateIsUnique = _plateController.text.isNotEmpty;
    });
  });

  _colorController.addListener(() {
    setState(() {
      listing.color = _colorController.text;
    });
  });

  WidgetsBinding.instance.addPostFrameCallback((_) => setState(() {}));
}

  bool _canContinue() {
    return listing.year != null &&
        listing.brand != null &&
        listing.model != null &&
        listing.bodyStyle != null &&
        listing.trim != null &&
        (listing.plateNumber?.isNotEmpty ?? false) &&
        (listing.color?.isNotEmpty ?? false);
  }

@override
Widget build(BuildContext context) {
  final isMotorcycle = widget.vehicleType == 'motorcycle';
  final brandsList = isMotorcycle ? motorcycleBrands : brands;
  final modelsMap = isMotorcycle ? motorcycleModelsByBrand : modelsByBrand;
  final bodyStylesList = isMotorcycle ? motorcycleBodyStyles : bodyStyles;
  
  return Scaffold(
    backgroundColor: Colors.white,
    appBar: AppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back, color: Colors.black),
        onPressed: () => Navigator.pop(context),
      ),
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
                  Text(
                    isMotorcycle ? 'What is your motorcycle?' : 'What is your car?',
                    style: GoogleFonts.poppins(
                      fontSize: 24,
                      fontWeight: FontWeight.w600,
                      color: Colors.black,
                    ),
                  ),
                  const SizedBox(height: 32),
                  _buildDropdown('Year', years, listing.year, (value) {
                    setState(() => listing.year = value);
                  }),
                  const SizedBox(height: 20),
                  _buildDropdown(
                    isMotorcycle ? 'Motorcycle Brand' : 'Car Brand',
                    brandsList,
                    listing.brand,
                    (value) {
                      setState(() {
                        listing.brand = value;
                        final models = modelsMap[value] ?? [];
                        listing.model = models.isNotEmpty ? models[0] : null;
                      });
                    }
                  ),
                  const SizedBox(height: 20),
                  _buildDropdown(
                    'Model',
                    listing.brand != null
                        ? (modelsMap[listing.brand!] ?? ['Select brand first'])
                        : ['Select brand first'],
                    listing.model,
                    (value) => setState(() => listing.model = value),
                  ),
                  const SizedBox(height: 20),
                  _buildDropdown('Body Style', bodyStylesList, listing.bodyStyle, (value) {
                    setState(() => listing.bodyStyle = value);
                  }),
                  const SizedBox(height: 20),
                  
                  if (isMotorcycle) ...[
                    _buildDropdown('Engine Displacement', engineDisplacements, listing.trim, (value) {
                      setState(() => listing.trim = value);
                    }),
                    const SizedBox(height: 20),
                    _buildDropdown('Transmission', transmissionTypes, null, (value) {
                      setState(() {
                        // You can add a transmission field to CarListing model if needed
                      });
                    }),
                  ] else ...[
                    _buildDropdown('Trim', ['N/A', 'Base', 'Sport', 'Luxury'], listing.trim, (value) {
                      setState(() => listing.trim = value);
                    }),
                  ],
                  
                  const SizedBox(height: 20),
                  _buildTextField('Plate Number', _plateController),
                  if (_plateIsUnique)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Row(
                        children: [
                          const Icon(Icons.check_circle, color: Colors.black, size: 16),
                          const SizedBox(width: 6),
                          Text(
                            'Plate number is unique.',
                            style: GoogleFonts.poppins(
                              fontSize: 13,
                              color: Colors.black87,
                            ),
                          ),
                        ],
                      ),
                    ),
                  const SizedBox(height: 20),
                  _buildTextField(
                    isMotorcycle ? 'Motorcycle Color' : 'Car Color',
                    _colorController
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(24),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _canContinue()
                    ? () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => CarPreferencesScreen(
                              listing: listing,
                              vehicleType: widget.vehicleType,
                            ),
                          ),
                        );
                      }
                    : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.black,
                  disabledBackgroundColor: const Color(0xFFE0E0E0),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(30),
                  ),
                ),
                child: Text(
                  'Continue',
                  style: GoogleFonts.poppins(
                    color: _canContinue() ? Colors.white : Colors.grey[500],
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

// 🔧 IMPROVED: Added validation to prevent invalid values
Widget _buildDropdown(String label, List<String> items, String? value, Function(String?) onChanged) {
  // Validate that the value exists in items
  final validValue = (value != null && items.contains(value)) ? value : null;
  
  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500, color: Colors.black87)),
      const SizedBox(height: 8),
      Container(
        decoration: BoxDecoration(
          color: const Color(0xFFF5F5F5),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE0E0E0)),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: DropdownButtonHideUnderline(
          child: DropdownButton<String>(
            value: validValue, // 🔧 Use validated value
            isExpanded: true,
            hint: Text('Select $label', style: GoogleFonts.poppins(color: Colors.black87)),
            icon: const Icon(Icons.keyboard_arrow_down, color: Colors.black),
            items: items.map((item) {
              return DropdownMenuItem(
                value: item,
                child: Text(item, style: GoogleFonts.poppins(fontSize: 14, color: Colors.black)),
              );
            }).toList(),
            onChanged: onChanged,
          ),
        ),
      ),
    ],
  );
}

Widget _buildTextField(String label, TextEditingController controller) {
  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: GoogleFonts.poppins(fontSize: 14, fontWeight: FontWeight.w500, color: Colors.black87)),
      const SizedBox(height: 8),
      TextField(
        controller: controller,
        style: GoogleFonts.poppins(fontSize: 14, color: Colors.black),
        decoration: InputDecoration(
          filled: true,
          fillColor: const Color(0xFFF5F5F5),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFE0E0E0)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFE0E0E0)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Colors.black, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        ),
      ),
    ],
  );
}
}