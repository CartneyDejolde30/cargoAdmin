class Car {
  final String image;
  final String name;
  final String location;
  final String price;
  final int seats;
  final double rating;

  Car({
    required this.image,
    required this.name,
    required this.location,
    required this.price,
    required this.seats,
    required this.rating,
  });

  factory Car.fromJson(Map<String, dynamic> json) {
    return Car(
      image: json['image'],
      name: "${json['brand']} ${json['model']}",
      location: json['location'] ?? "Unknown",
      price: "₱${json['price']}/day",
      seats: json['seats'] ?? 4,
      rating: double.tryParse(json['rating'].toString()) ?? 5.0,
    );
  }
}
