import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class EmptyCarState extends StatelessWidget {
  final String searchQuery;

  const EmptyCarState({
    super.key,
    required this.searchQuery,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Colors.grey.shade100,
              shape: BoxShape.circle,
            ),
            child: Icon(
              searchQuery.isEmpty 
                  ? Icons.directions_car_outlined 
                  : Icons.search_off_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
          ),
          const SizedBox(height: 24),
          Text(
            searchQuery.isEmpty ? "No cars yet" : "No matching results",
            style: GoogleFonts.poppins(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 48),
            child: Text(
              searchQuery.isEmpty
                  ? "Add your first car to get started"
                  : "Try adjusting your search or filters",
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}