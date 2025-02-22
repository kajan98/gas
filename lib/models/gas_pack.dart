class GasPack {
  final int id;
  final String litro;
  final String packName;
  final double maxRetailPrice;
  int quantity;

  GasPack({
    required this.id,
    required this.litro,
    required this.packName,
    required this.maxRetailPrice,
    this.quantity = 0,
  });

  factory GasPack.fromJson(Map<String, dynamic> json) {
    return GasPack(
      id: json['id'],
      litro: json['litro'],
      packName: json['pack_name'],
      maxRetailPrice: double.parse(json['max_retail_price'].toString()),
    );
  }
} 