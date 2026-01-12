import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class FAQsScreen extends StatefulWidget {
  const FAQsScreen({super.key});

  @override
  State<FAQsScreen> createState() => _FAQsScreenState();
}

class _FAQsScreenState extends State<FAQsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int _expandedIndex = -1;
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  // Guest (Renter) FAQs
  final List<FAQItem> guestFAQs = [
    // Getting Started
    FAQItem(
      category: 'Getting Started',
      question: 'What is Cargo?',
      answer: 'Cargo is a peer-to-peer car rental platform that connects car owners with renters. It allows you to rent vehicles directly from local car owners at affordable rates, providing flexible and convenient transportation options.',
      icon: Icons.info_outline,
    ),
    FAQItem(
      category: 'Getting Started',
      question: 'What is peer-to-peer car sharing?',
      answer: 'Peer-to-peer car sharing is a model where individual car owners rent out their personal vehicles to other users through a digital platform. This creates a community-driven marketplace for vehicle rentals.',
      icon: Icons.people_outline,
    ),
    FAQItem(
      category: 'Getting Started',
      question: 'What is Cargo\'s rating system?',
      answer: 'Our rating system allows both renters and owners to rate each other after each trip. Ratings are based on factors like vehicle condition, cleanliness, communication, and overall experience. This helps build trust within the community.',
      icon: Icons.star_outline,
    ),
    FAQItem(
      category: 'Getting Started',
      question: 'Why should I rent with Cargo?',
      answer: 'Cargo offers affordable rates compared to traditional rental companies, flexible rental periods, a wide variety of vehicles from local owners, secure payment through GCash, GPS tracking for safety, and a verified community of users.',
      icon: Icons.thumb_up,
    ),
    FAQItem(
      category: 'Getting Started',
      question: 'Am I eligible to rent a car on Cargo?',
      answer: 'To rent on Cargo, you must be at least 18 years old, have a valid government-issued ID, complete identity verification including selfie authentication, and have a smartphone with internet connectivity.',
      icon: Icons.verified_user_outlined,
    ),
    FAQItem(
      category: 'Getting Started',
      question: 'How do I create an account?',
      answer: 'Download the Cargo app, click "Sign Up", enter your email and create a password, verify your email, complete your profile with your name and contact information, upload a government-issued ID, and complete selfie verification.',
      icon: Icons.account_circle_outlined,
    ),
    FAQItem(
      category: 'Getting Started',
      question: 'What purposes can I use the rented vehicle for?',
      answer: 'Rented vehicles can be used for personal transportation, business trips, family outings, and weekend getaways. However, commercial activities like ride-sharing services and racing are prohibited unless explicitly allowed by the owner.',
      icon: Icons.directions_car_outlined,
    ),

    // Booking
    FAQItem(
      category: 'Booking',
      question: 'Selecting pickup, delivery and return',
      answer: 'When booking, you can choose from different delivery methods: Guest Pickup (you pick up at owner\'s location), Host Delivery (owner delivers to you), or custom arrangements. Return follows the same options based on what was agreed.',
      icon: Icons.location_on_outlined,
    ),
    FAQItem(
      category: 'Booking',
      question: 'What all is included in the rental price?',
      answer: 'The rental price includes the vehicle for the specified duration, basic insurance coverage (if provided by owner), GPS tracking during your trip, and 24/7 customer support. Additional features like delivery or driver services may incur extra charges.',
      icon: Icons.receipt_outlined,
    ),
    FAQItem(
      category: 'Booking',
      question: 'Is there a minimum and maximum rental period?',
      answer: 'Minimum rental periods are typically set by individual car owners (usually 1-3 days). Maximum periods can range from 1 week to 3 months depending on the owner\'s preferences. Check each vehicle listing for specific details.',
      icon: Icons.calendar_today_outlined,
    ),
    FAQItem(
      category: 'Booking',
      question: 'Can I travel take an inter-island RoRo ferry?',
      answer: 'Inter-island travel requires explicit permission from the car owner. Some owners allow it with advance notice and possibly an additional fee. Always discuss travel plans with the owner before booking.',
      icon: Icons.sailing_outlined,
    ),
    FAQItem(
      category: 'Booking',
      question: 'What is the Check Out and Return Process?',
      answer: 'Check-out: Meet the owner at agreed location, inspect vehicle together and document condition with photos, sign the rental agreement digitally, receive the keys. Return: Return vehicle on time with same fuel level, inspect together, take return photos, and confirm completion in the app.',
      icon: Icons.key_outlined,
    ),

    // Payment, Pricing and Refunds
    FAQItem(
      category: 'Payment, Pricing and Refunds',
      question: 'What are the payment methods?',
      answer: 'Cargo currently accepts payments through GCash for secure digital transactions. Payment is processed when you confirm your booking and is held securely until trip completion.',
      icon: Icons.payment_outlined,
    ),
    FAQItem(
      category: 'Payment, Pricing and Refunds',
      question: 'Do I get money back if I end a trip early?',
      answer: 'Early returns may be eligible for partial refunds depending on the owner\'s cancellation policy. Contact the owner through in-app messaging to discuss early return. Refunds are processed within 5-7 business days.',
      icon: Icons.schedule_outlined,
    ),
    FAQItem(
      category: 'Payment, Pricing and Refunds',
      question: 'Does Cargo charge a security deposit for rentals?',
      answer: 'Yes, a refundable security deposit is required for all bookings. The amount varies by vehicle type and rental duration (typically ₱2,000-₱10,000). The deposit is held during your trip and refunded within 3-5 days after successful return.',
      icon: Icons.account_balance_wallet_outlined,
    ),
    FAQItem(
      category: 'Payment, Pricing and Refunds',
      question: 'I just made a booking request and payment has been deducted from my card?',
      answer: 'When you make a booking request, the payment is authorized but not charged immediately. The actual charge occurs only when the owner accepts your request. If declined, the authorization is released within 24-48 hours.',
      icon: Icons.credit_card_outlined,
    ),

    // Safety and Car Rules
    FAQItem(
      category: 'Safety and Car Rules',
      question: 'What is the Cargo Fuel Policy?',
      answer: 'You must return the vehicle with the same fuel level as when you received it. If returned with less fuel, you will be charged for the difference plus a refueling service fee.',
      icon: Icons.local_gas_station_outlined,
    ),
    FAQItem(
      category: 'Safety and Car Rules',
      question: 'What happens if my car has a flat tire?',
      answer: 'Contact the owner immediately through the app. Most vehicles have spare tires and tools. For assistance, you can also contact Cargo support. Document the issue with photos. Costs may be covered depending on the cause.',
      icon: Icons.tire_repair_outlined,
    ),
    FAQItem(
      category: 'Safety and Car Rules',
      question: 'What happens if the car battery dies?',
      answer: 'Contact the owner and Cargo support immediately. If the battery dies due to normal use, the owner is responsible for replacement. If due to negligence (lights left on), you may be responsible for jump-start or replacement costs.',
      icon: Icons.battery_alert_outlined,
    ),
    FAQItem(
      category: 'Safety and Car Rules',
      question: 'What happens if my car has a breakdown?',
      answer: 'Pull over safely, contact the owner immediately through the app, document the situation with photos, contact Cargo support for assistance. The owner is responsible for mechanical failures not caused by misuse.',
      icon: Icons.build_outlined,
    ),
    FAQItem(
      category: 'Safety and Car Rules',
      question: 'What happens if I have an accident?',
      answer: 'Ensure everyone\'s safety first, call emergency services if needed, take photos of all vehicles and damage, exchange information with other parties, file a police report, immediately notify the owner and Cargo support through the app, and do not admit fault.',
      icon: Icons.car_crash_outlined,
    ),
    FAQItem(
      category: 'Safety and Car Rules',
      question: 'How are rentals insured?',
      answer: 'Insurance coverage varies by owner. Some provide comprehensive coverage, while others require you to have your own insurance. Always clarify insurance details before booking. Cargo recommends all users maintain valid insurance coverage.',
      icon: Icons.shield_outlined,
    ),

    // Platform Policies
    FAQItem(
      category: 'Platform Policies',
      question: 'Platform User Agreement',
      answer: 'View our complete Terms of Service governing the use of the Cargo platform, including rights, responsibilities, and dispute resolution procedures.',
      isDocument: true,
      icon: Icons.gavel_outlined,
    ),
    FAQItem(
      category: 'Platform Policies',
      question: 'Key Policy',
      answer: 'Guidelines on key handover procedures, spare key requirements, and what to do if keys are lost or locked inside the vehicle.',
      isDocument: true,
      icon: Icons.vpn_key_outlined,
    ),
    FAQItem(
      category: 'Platform Policies',
      question: 'Privacy Policy',
      answer: 'Learn how Cargo collects, uses, and protects your personal data in compliance with the Philippine Data Privacy Act.',
      isDocument: true,
      icon: Icons.privacy_tip_outlined,
    ),
    FAQItem(
      category: 'Platform Policies',
      question: 'Vehicle Lease Agreement',
      answer: 'Standard rental agreement template outlining terms, conditions, responsibilities, and liabilities for both renters and owners.',
      isDocument: true,
      icon: Icons.description_outlined,
    ),
  ];

  // Host (Owner) FAQs
  final List<FAQItem> hostFAQs = [
    // About Hosting
    FAQItem(
      category: 'About Hosting',
      question: 'What is the process of registering my car on Cargo?',
      answer: '1. Create an owner account and complete verification\n2. Click "List Your Car"\n3. Enter vehicle details (make, model, year)\n4. Upload clear photos of your vehicle\n5. Set your rental price and availability\n6. Upload required documents (OR/CR)\n7. Submit for admin approval\n8. Once approved, your car will be visible to renters',
      icon: Icons.app_registration_outlined,
    ),
    FAQItem(
      category: 'About Hosting',
      question: 'What vehicles are eligible for Cargo?',
      answer: 'Eligible vehicles must: be registered in your name or you must have legal authority to rent it, have valid registration and insurance, be in good working condition, pass safety inspection, have clean interior and exterior, be less than 15 years old (preferred), and have no major mechanical issues.',
      icon: Icons.checklist_outlined,
    ),
    FAQItem(
      category: 'About Hosting',
      question: 'Why should I share my car?',
      answer: 'Generate passive income from an underutilized asset, offset car maintenance and depreciation costs, help your community access affordable transportation, meet new people, maintain your vehicle\'s active use, and contribute to sustainable resource sharing.',
      icon: Icons.share_outlined,
    ),
    FAQItem(
      category: 'About Hosting',
      question: 'Is it compulsory to rent out if I sign up my car?',
      answer: 'No, listing your car does not obligate you to accept every booking. You have full control over your calendar, can set availability, accept or decline booking requests, block dates when needed, and temporarily disable your listing anytime.',
      icon: Icons.block_outlined,
    ),
    FAQItem(
      category: 'About Hosting',
      question: 'How can I rent out my car as often as possible?',
      answer: 'Keep your calendar updated regularly, respond quickly to booking requests (within 1 hour), maintain competitive pricing, provide excellent customer service, keep your car clean and well-maintained, upload high-quality photos, earn positive reviews, and offer flexible pickup/delivery options.',
      icon: Icons.trending_up_outlined,
    ),

    // Booking
    FAQItem(
      category: 'Booking',
      question: 'What happens if a guest returns my car late?',
      answer: 'Contact the renter through the app to check their status. Late fees are automatically calculated (typically 10-15% of daily rate per hour). If significantly late without communication, contact Cargo support. Use GPS tracking to locate your vehicle if needed.',
      icon: Icons.access_time_outlined,
    ),
    FAQItem(
      category: 'Booking',
      question: 'What is the Check Out and Return Process?',
      answer: 'Check-out: Meet renter at agreed time/location, verify their identity, inspect vehicle together and document condition with photos in the app, explain vehicle features and rules, hand over keys, confirm start of trip in app. Return: Meet at agreed time/location, inspect vehicle together, check for damages or issues, verify fuel level, take return photos in app, collect keys, confirm trip completion.',
      icon: Icons.swap_horiz_outlined,
    ),
    FAQItem(
      category: 'Booking',
      question: 'How much time do I have to wait in-between guest rentals?',
      answer: 'It\'s recommended to allow at least 2-3 hours between bookings for vehicle inspection, cleaning, and addressing any issues. You can set your own buffer times in your availability settings to prevent back-to-back bookings.',
      icon: Icons.timer_outlined,
    ),

    // Earnings and Taxes
    FAQItem(
      category: 'Earnings and Taxes',
      question: 'How much can I earn as a host?',
      answer: 'Earnings vary based on vehicle type, location, pricing, and availability. On average, owners earn ₱5,000-₱25,000 per month. Premium vehicles in high-demand areas can earn more. Cargo takes a platform fee (typically 15-20%) from each booking.',
      icon: Icons.attach_money_outlined,
    ),
    FAQItem(
      category: 'Earnings and Taxes',
      question: 'How much does it cost to list a car?',
      answer: 'Listing your car on Cargo is completely FREE. There are no upfront fees, monthly subscriptions, or listing charges. Cargo only takes a commission from successful bookings.',
      icon: Icons.money_off_outlined,
    ),

    // Safety and Listing Management
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'Is it safe to rent out my car to a stranger?',
      answer: 'Cargo implements multiple safety measures: government ID verification for all renters, selfie authentication, GPS tracking during trips, security deposits held until return, rating system for accountability, in-app messaging for documentation, and 24/7 support for issues.',
      icon: Icons.security_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'How is the safety of my car insured?',
      answer: 'Security deposits cover minor damages, GPS tracking monitors vehicle location, all renters are verified users, you can review renter profiles before accepting, document vehicle condition before/after trips, and maintain your own comprehensive insurance coverage.',
      icon: Icons.verified_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'What protection do I have against car damage?',
      answer: 'Security deposits can cover repair costs, photo documentation before/after trips establishes liability, damage reporting system in the app, dispute resolution support from Cargo, and recommendations to maintain comprehensive insurance coverage.',
      icon: Icons.health_and_safety_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'What happens if a guest has a flat tire in my car?',
      answer: 'Ensure your car has a working spare tire and tools. The renter should contact you immediately. Normal tire issues are typically the owner\'s responsibility. Document everything through the app. Consider what constitutes normal wear vs. negligence.',
      icon: Icons.tire_repair_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'Are there Maintenance requirements?',
      answer: 'Yes, as an owner you must ensure regular oil changes and servicing, functional brakes and lights, proper tire condition and pressure, clean interior and exterior, valid registration and insurance, and working safety equipment (spare tire, jack, warning triangle).',
      icon: Icons.construction_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'What are the cleaning policies?',
      answer: 'Vehicles must be clean for each renter. Renters are expected to return vehicles in similar condition. If returned excessively dirty, you can charge a cleaning fee from the security deposit (typically ₱500-₱1,500 depending on condition).',
      icon: Icons.cleaning_services_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'What is the Cargo Fuel Policy?',
      answer: 'Renters must return vehicles with the same fuel level as pickup. Set clear fuel expectations before trip. Document fuel level with photos. Charge for missing fuel plus a convenience fee if returned with less fuel.',
      icon: Icons.local_gas_station_outlined,
    ),
    FAQItem(
      category: 'Safety and Listing Management',
      question: 'What kind of uses of my car are permitted and what is not allowed?',
      answer: 'Permitted: Personal transportation, business trips, family vacations, weekend getaways. NOT Allowed: Commercial ride-sharing (Grab/Uber), racing or competitions, off-road driving (unless specified), smoking inside vehicle, pet transportation (unless allowed), illegal activities.',
      icon: Icons.rule_outlined,
    ),

    // Platform Policies
    FAQItem(
      category: 'Platform Policies',
      question: 'Platform User Agreement',
      answer: 'Complete Terms of Service for car owners including hosting responsibilities, liability, and platform rules.',
      isDocument: true,
      icon: Icons.gavel_outlined,
    ),
    FAQItem(
      category: 'Platform Policies',
      question: 'Key Policy',
      answer: 'Requirements for key management, spare keys, and procedures for lost keys.',
      isDocument: true,
      icon: Icons.vpn_key_outlined,
    ),
    FAQItem(
      category: 'Platform Policies',
      question: 'Privacy Policy',
      answer: 'How Cargo handles owner data, renter information, and GPS tracking data.',
      isDocument: true,
      icon: Icons.privacy_tip_outlined,
    ),
    FAQItem(
      category: 'Platform Policies',
      question: 'Vehicle Lease Agreement',
      answer: 'Standard rental agreement template that governs owner-renter transactions.',
      isDocument: true,
      icon: Icons.description_outlined,
    ),
  ];

  List<FAQItem> _filterFAQs(List<FAQItem> faqs) {
    if (_searchQuery.isEmpty) return faqs;
    
    return faqs.where((faq) {
      return faq.question.toLowerCase().contains(_searchQuery.toLowerCase()) ||
             faq.answer.toLowerCase().contains(_searchQuery.toLowerCase()) ||
             faq.category.toLowerCase().contains(_searchQuery.toLowerCase());
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'FAQs & Help',
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: false,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(
            color: Colors.grey.shade200,
            height: 1,
          ),
        ),
      ),
      body: Column(
        children: [
          // Search Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
            child: Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF5F5F5),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: TextField(
                controller: _searchController,
                onChanged: (value) {
                  setState(() {
                    _searchQuery = value;
                  });
                },
                style: GoogleFonts.poppins(fontSize: 14),
                decoration: InputDecoration(
                  hintText: 'Search FAQs...',
                  hintStyle: GoogleFonts.poppins(
                    color: Colors.grey.shade500,
                    fontSize: 14,
                  ),
                  prefixIcon: Icon(Icons.search, color: Colors.grey.shade600),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: Icon(Icons.clear, color: Colors.grey.shade600),
                          onPressed: () {
                            _searchController.clear();
                            setState(() {
                              _searchQuery = '';
                            });
                          },
                        )
                      : null,
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                ),
              ),
            ),
          ),

          // Tab Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF5F5F5),
                borderRadius: BorderRadius.circular(12),
              ),
              child: TabBar(
                controller: _tabController,
                indicator: BoxDecoration(
                  color: Colors.black,
                  borderRadius: BorderRadius.circular(10),
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.black87,
                labelStyle: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
                unselectedLabelStyle: GoogleFonts.poppins(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
                dividerColor: Colors.transparent,
                tabs: const [
                  Tab(text: 'Guest'),
                  Tab(text: 'Host'),
                ],
              ),
            ),
          ),

          // Tab Content
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildFAQList(_filterFAQs(guestFAQs), 'guest'),
                _buildFAQList(_filterFAQs(hostFAQs), 'host'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFAQList(List<FAQItem> faqs, String type) {
    if (faqs.isEmpty && _searchQuery.isNotEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.search_off,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            Text(
              'No results found',
              style: GoogleFonts.poppins(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: Colors.grey.shade700,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Try searching with different keywords',
              style: GoogleFonts.poppins(
                fontSize: 14,
                color: Colors.grey.shade500,
              ),
            ),
          ],
        ),
      );
    }

    // Group FAQs by category
    Map<String, List<FAQItem>> groupedFAQs = {};
    for (var faq in faqs) {
      if (!groupedFAQs.containsKey(faq.category)) {
        groupedFAQs[faq.category] = [];
      }
      groupedFAQs[faq.category]!.add(faq);
    }

    return ListView.builder(
      padding: const EdgeInsets.only(left: 16, right: 16, top: 8, bottom: 100),
      itemCount: groupedFAQs.length,
      itemBuilder: (context, categoryIndex) {
        String category = groupedFAQs.keys.elementAt(categoryIndex);
        List<FAQItem> categoryFAQs = groupedFAQs[category]!;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Category Header
            if (category.isNotEmpty) ...[
              Padding(
                padding: const EdgeInsets.only(top: 20, bottom: 12, left: 4),
                child: Text(
                  category.toUpperCase(),
                  style: GoogleFonts.poppins(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: Colors.grey.shade600,
                    letterSpacing: 1.2,
                  ),
                ),
              ),
            ],

            // FAQ Items
            ...categoryFAQs.map((faq) {
              int globalIndex = faqs.indexOf(faq);
              return _buildFAQItem(faq, globalIndex, type);
            }).toList(),
          ],
        );
      },
    );
  }

  Widget _buildFAQItem(FAQItem faq, int index, String type) {
    bool isExpanded = _expandedIndex == index;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isExpanded ? Colors.black : Colors.grey.shade200,
          width: isExpanded ? 2 : 1,
        ),
        boxShadow: isExpanded
            ? [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ]
            : [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
      ),
      child: Column(
        children: [
          InkWell(
            onTap: () {
              setState(() {
                _expandedIndex = isExpanded ? -1 : index;
              });
            },
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  // Icon
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: faq.isDocument 
                          ? Colors.blue.shade50 
                          : Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      faq.icon,
                      size: 22,
                      color: faq.isDocument 
                          ? Colors.blue.shade700 
                          : Colors.grey.shade700,
                    ),
                  ),
                  const SizedBox(width: 14),
                  
                  // Question Text
                  Expanded(
                    child: Text(
                      faq.question,
                      style: GoogleFonts.poppins(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.black87,
                        height: 1.4,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  
                  // Expand Icon
                  AnimatedRotation(
                    duration: const Duration(milliseconds: 200),
                    turns: isExpanded ? 0.5 : 0,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: isExpanded 
                            ? Colors.black 
                            : Colors.grey.shade200,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(
                        Icons.keyboard_arrow_down,
                        color: isExpanded ? Colors.white : Colors.grey.shade700,
                        size: 20,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          
          // Expanded Content
          AnimatedCrossFade(
            firstChild: const SizedBox.shrink(),
            secondChild: Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    height: 1,
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          Colors.grey.shade300,
                          Colors.grey.shade100,
                        ],
                      ),
                    ),
                  ),
                  
                  // Answer Text
                  Text(
                    faq.answer,
                    style: GoogleFonts.poppins(
                      fontSize: 13.5,
                      color: Colors.grey.shade700,
                      height: 1.7,
                    ),
                  ),
                  
                  // Document Button
                  if (faq.isDocument) ...[
                    const SizedBox(height: 16),
                    InkWell(
                      onTap: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(
                              'Opening ${faq.question}...',
                              style: GoogleFonts.poppins(fontSize: 14),
                            ),
                            duration: const Duration(seconds: 2),
                            backgroundColor: Colors.black87,
                            behavior: SnackBarBehavior.floating,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                        );
                      },
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.blue.shade50,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: Colors.blue.shade200,
                            width: 1,
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              Icons.open_in_new,
                              size: 18,
                              color: Colors.blue.shade700,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'View Document',
                              style: GoogleFonts.poppins(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Colors.blue.shade700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            crossFadeState: isExpanded
                ? CrossFadeState.showSecond
                : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 300),
          ),
        ],
      ),
    );
  }
}

class FAQItem {
  final String category;
  final String question;
  final String answer;
  final bool isDocument;
  final IconData icon;

  FAQItem({
    required this.category,
    required this.question,
    required this.answer,
    this.isDocument = false,
    this.icon = Icons.help_outline,
  });
}