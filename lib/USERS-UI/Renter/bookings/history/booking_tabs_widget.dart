import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class BookingTabsWidget extends StatelessWidget {
  final int currentTabIndex;
  final Function(int) onTabChanged;
  final List<String> tabs;
  final List<int>? badgeCounts; // Optional badge counts for each tab

  const BookingTabsWidget({
    super.key,
    required this.currentTabIndex,
    required this.onTabChanged,
    this.tabs = const ['Active', 'Pending', 'Upcoming', 'Past'],
    this.badgeCounts,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 20),
      padding: EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: List.generate(
          tabs.length,
          (index) => _buildTab(
            label: tabs[index],
            index: index,
            badgeCount: badgeCounts != null && index < badgeCounts!.length
                ? badgeCounts![index]
                : null,
          ),
        ),
      ),
    );
  }

  Widget _buildTab({
    required String label,
    required int index,
    int? badgeCount,
  }) {
    bool isSelected = currentTabIndex == index;
    
    return Expanded(
      child: GestureDetector(
        onTap: () => onTabChanged(index),
        child: Container(
          padding: EdgeInsets.symmetric(vertical: 12, horizontal: 8),
          decoration: BoxDecoration(
            color: isSelected ? Colors.black : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Center(
                child: Text(
                  label,
                  textAlign: TextAlign.center,
                  style: GoogleFonts.poppins(
                    fontSize: 13,
                    fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                    color: isSelected ? Colors.white : Colors.grey.shade600,
                  ),
                ),
              ),
              // Optional badge
              if (badgeCount != null && badgeCount > 0)
                Positioned(
                  top: -4,
                  right: -4,
                  child: Container(
                    padding: EdgeInsets.all(4),
                    constraints: BoxConstraints(
                      minWidth: 18,
                      minHeight: 18,
                    ),
                    decoration: BoxDecoration(
                      color: isSelected ? Colors.white : Colors.black,
                      shape: BoxShape.circle,
                    ),
                    child: Center(
                      child: Text(
                        badgeCount > 99 ? '99+' : badgeCount.toString(),
                        style: GoogleFonts.poppins(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: isSelected ? Colors.black : Colors.white,
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

// Alternative style - Underline tabs (if you want a different look)
class BookingTabsUnderlineWidget extends StatelessWidget {
  final int currentTabIndex;
  final Function(int) onTabChanged;
  final List<String> tabs;

  const BookingTabsUnderlineWidget({
    super.key,
    required this.currentTabIndex,
    required this.onTabChanged,
    this.tabs = const ['Active', 'Pending', 'Upcoming', 'Past'],
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        children: List.generate(
          tabs.length,
          (index) => _buildTab(tabs[index], index),
        ),
      ),
    );
  }

  Widget _buildTab(String label, int index) {
    bool isSelected = currentTabIndex == index;
    
    return Expanded(
      child: GestureDetector(
        onTap: () => onTabChanged(index),
        child: Column(
          children: [
            Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: Text(
                label,
                textAlign: TextAlign.center,
                style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                  color: isSelected ? Colors.black : Colors.grey.shade600,
                ),
              ),
            ),
            Container(
              height: 2,
              decoration: BoxDecoration(
                color: isSelected ? Colors.black : Colors.transparent,
                borderRadius: BorderRadius.circular(1),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Alternative style - Segmented Control (iOS style)
class BookingTabsSegmentedWidget extends StatelessWidget {
  final int currentTabIndex;
  final Function(int) onTabChanged;
  final List<String> tabs;

  const BookingTabsSegmentedWidget({
    super.key,
    required this.currentTabIndex,
    required this.onTabChanged,
    this.tabs = const ['Active', 'Pending', 'Upcoming', 'Past'],
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(horizontal: 20),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade300),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: List.generate(
          tabs.length,
          (index) => _buildTab(tabs[index], index),
        ),
      ),
    );
  }

  Widget _buildTab(String label, int index) {
    bool isSelected = currentTabIndex == index;
    bool isFirst = index == 0;
    bool isLast = index == tabs.length - 1;
    
    return Expanded(
      child: GestureDetector(
        onTap: () => onTabChanged(index),
        child: Container(
          padding: EdgeInsets.symmetric(vertical: 12, horizontal: 8),
          decoration: BoxDecoration(
            color: isSelected ? Colors.black : Colors.white,
            borderRadius: BorderRadius.only(
              topLeft: isFirst ? Radius.circular(11) : Radius.zero,
              bottomLeft: isFirst ? Radius.circular(11) : Radius.zero,
              topRight: isLast ? Radius.circular(11) : Radius.zero,
              bottomRight: isLast ? Radius.circular(11) : Radius.zero,
            ),
            border: Border(
              right: !isLast
                  ? BorderSide(color: Colors.grey.shade300, width: 1)
                  : BorderSide.none,
            ),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(
              fontSize: 13,
              fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
              color: isSelected ? Colors.white : Colors.grey.shade600,
            ),
          ),
        ),
      ),
    );
  }
}