class OpportunityDetail {
  final String benefits;
  final String applicationProcess;
  final String furtherQueries;
  final String eligibilities;
  final String startDate;
  final String endDate;
  final String officialLink;
  final String eligibleRegions;

  OpportunityDetail.fromJson(Map<String, dynamic> jsonMap)
      : benefits = jsonMap['benefits'] ?? "",
        applicationProcess = jsonMap['applicationProcess'] ?? "",
        furtherQueries = jsonMap['furtherQueries'] ?? "",
        eligibilities = jsonMap['eligibilities'] ?? "",
        startDate = jsonMap['startDate'] ?? "",
        endDate = jsonMap['endDate'] ?? "",
        officialLink = jsonMap['officialLink'] ?? "",
        eligibleRegions = jsonMap['eligibleRegions'] ?? "";
}
