import 'package:flutter/material.dart';
import 'package:forsat/application/classes/opportunity/opportunity.dart';
import 'package:forsat/widgets/opportunity_links_widget.dart';

class OpportunityDetailPage extends StatefulWidget {
  @override
  _OpportunityDetailPageState createState() => _OpportunityDetailPageState();
}

class _OpportunityDetailPageState extends State<OpportunityDetailPage> {
  @override
  Widget build(BuildContext context) {
    final Opportunity opportunity =
        ModalRoute.of(context).settings.arguments as Opportunity;

    return Scaffold(
      body: SingleChildScrollView(
        child: Column(
          children: [
            Image.asset("assets/images/test_image.png"),
            Container(
              padding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              child: Column(
                children: [
                  Text(
                    "${opportunity.title}",
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  OpportunityLinksWidget(
                    categoryName: opportunity.category.name,
                    views: opportunity.id.toString(),
                    deadline: opportunity.deadline,
                  ),
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: 12, vertical: 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TitleTextWidget(
                          title: "Benefits:",
                        ),
                        heightSpacer(),
                        Text(
                          opportunity.opportunityDetail.benefits,
                        ),
                        heightSpacer(),
                        TitleTextWidget(
                          title: "Application Process:",
                        ),
                        heightSpacer(),
                        Text(
                          opportunity.opportunityDetail.applicationProcess,
                        ),
                        heightSpacer(),
                        TitleTextWidget(
                          title: "Further Queries:",
                        ),
                        heightSpacer(),
                        Text(
                          opportunity.opportunityDetail.furtherQueries,
                        ),
                        heightSpacer(),
                        TitleTextWidget(
                          title: "Eligibilities:",
                        ),
                        heightSpacer(),
                        Text(
                          opportunity.opportunityDetail.eligibilities,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  SizedBox heightSpacer() => SizedBox(height: 15);
}

class TitleTextWidget extends StatelessWidget {
  final String title;

  const TitleTextWidget({Key key, this.title}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 20,
        fontWeight: FontWeight.bold,
      ),
    );
  }
}
