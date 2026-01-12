// lib/USERS-UI/Renter/bookings/pricing_calculator.dart

class PricingCalculator {
  // Base configuration
  static const double driverFeePerDay = 600.0; // ₱600/day in PH
  static const double insuranceRate = 0.12; // 12% of base rental
  static const double deliveryFeeBase = 300.0; // Base delivery fee
  static const double deliveryFeePerKm = 15.0; // ₱15 per km
  
  // Discount rates
  static const double weeklyDiscountRate = 0.12; // 12% off
  static const double monthlyDiscountRate = 0.25; // 25% off
  
  // Mileage
  static const double excessMileageRate = 10.0; // ₱10 per km over limit
  static const int dailyMileageLimit = 200; // km per day
  
  // Late fees
  static const double lateReturnFeePerHour = 300.0;
  static const double cleaningFee = 400.0;
  
  /// Calculate total booking price
  static BookingPriceBreakdown calculatePrice({
    required double pricePerDay,
    required int numberOfDays,
    required bool withDriver,
    required String rentalPeriod, // 'Day', 'Weekly', 'Monthly'
    bool needsDelivery = false,
    double deliveryDistance = 0.0,
    bool includeInsurance = true,
  }) {
    
    // 1. Base rental cost
    double baseRental = pricePerDay * numberOfDays;
    
    // 2. Apply period discounts
    double discount = 0.0;
    if (rentalPeriod == 'Weekly' && numberOfDays >= 7) {
      discount = baseRental * weeklyDiscountRate;
    } else if (rentalPeriod == 'Monthly' && numberOfDays >= 30) {
      discount = baseRental * monthlyDiscountRate;
    }
    
    double discountedRental = baseRental - discount;
    
    // 3. Driver fee
    double driverFee = withDriver ? (driverFeePerDay * numberOfDays) : 0.0;
    
    // 4. Insurance fee
    double insuranceFee = includeInsurance ? (discountedRental * insuranceRate) : 0.0;
    
    // 5. Delivery fee
    double deliveryFee = 0.0;
    if (needsDelivery) {
      deliveryFee = deliveryFeeBase + (deliveryDistance * deliveryFeePerKm);
    }
    
    // 6. Calculate subtotal
    double subtotal = discountedRental + driverFee + insuranceFee + deliveryFee;
    
    // 7. Service fee (platform fee) - 5%
    double serviceFee = subtotal * 0.05;
    
    // 8. Total amount
    double totalAmount = subtotal + serviceFee;
    
    return BookingPriceBreakdown(
      baseRental: baseRental,
      discount: discount,
      discountedRental: discountedRental,
      driverFee: driverFee,
      insuranceFee: insuranceFee,
      deliveryFee: deliveryFee,
      serviceFee: serviceFee,
      subtotal: subtotal,
      totalAmount: totalAmount,
      numberOfDays: numberOfDays,
      pricePerDay: pricePerDay,
    );
  }
  
  /// Calculate excess mileage charges
  static double calculateExcessMileage({
    required int actualMileage,
    required int numberOfDays,
  }) {
    int allowedMileage = dailyMileageLimit * numberOfDays;
    int excessMileage = actualMileage - allowedMileage;
    
    if (excessMileage > 0) {
      return excessMileage * excessMileageRate;
    }
    return 0.0;
  }
  
  /// Calculate late return penalty
  static double calculateLateReturnFee(int hoursLate) {
    if (hoursLate <= 0) return 0.0;
    return hoursLate * lateReturnFeePerHour;
  }
  
  /// Format currency for display
  static String formatCurrency(double amount) {
    return '₱${amount.toStringAsFixed(2).replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]},',
    )}';
  }
}

/// Data class for price breakdown
class BookingPriceBreakdown {
  final double baseRental;
  final double discount;
  final double discountedRental;
  final double driverFee;
  final double insuranceFee;
  final double deliveryFee;
  final double serviceFee;
  final double subtotal;
  final double totalAmount;
  final int numberOfDays;
  final double pricePerDay;
  
  BookingPriceBreakdown({
    required this.baseRental,
    required this.discount,
    required this.discountedRental,
    required this.driverFee,
    required this.insuranceFee,
    required this.deliveryFee,
    required this.serviceFee,
    required this.subtotal,
    required this.totalAmount,
    required this.numberOfDays,
    required this.pricePerDay,
  });
  
  /// Get discount percentage applied
  double get discountPercentage {
    if (baseRental == 0) return 0.0;
    return (discount / baseRental) * 100;
  }
  
  /// Get daily rate after all fees
  double get effectiveDailyRate {
    if (numberOfDays == 0) return 0.0;
    return totalAmount / numberOfDays;
  }
  
  Map<String, dynamic> toJson() => {
    'baseRental': baseRental,
    'discount': discount,
    'discountedRental': discountedRental,
    'driverFee': driverFee,
    'insuranceFee': insuranceFee,
    'deliveryFee': deliveryFee,
    'serviceFee': serviceFee,
    'subtotal': subtotal,
    'totalAmount': totalAmount,
    'numberOfDays': numberOfDays,
    'pricePerDay': pricePerDay,
  };
}