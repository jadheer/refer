import 'package:flutter/material.dart';
import 'package:forsat/application/state/opportunity_state.dart';
import 'package:forsat/router/route_constants.dart';
import 'package:forsat/widgets/opportunity_links_widget.dart';
import 'package:states_rebuilder/states_rebuilder.dart';

class OpportunitiesPage extends StatefulWidget {
  @override
  _OpportunitiesPageState createState() => _OpportunitiesPageState();
}

class _OpportunitiesPageState extends State<OpportunitiesPage>
    with AutomaticKeepAliveClientMixin {
  final _opportunitiesStateRM = RM.get<OpportunityState>();
  ScrollController _scrollController;

  @override
  void didChangeDependencies() {
    _getNewOpportunities();
    _scrollController = ScrollController();
    _scrollController.addListener(() {
      // print(_scrollController.position.maxScrollExtent);
      double currentPosition = _scrollController.position.pixels;
      double maxScrollExtend = _scrollController.position.maxScrollExtent;

      if (currentPosition >= maxScrollExtend &&
          !_opportunitiesStateRM.state.loading) {
        _getNewOpportunities();
      }
    });

    super.didChangeDependencies();
  }

  void _getNewOpportunities() {
    _opportunitiesStateRM
        .setState((opportunityState) => opportunityState.getAllOpportunities());
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    TextStyle _iconTextStyle = TextStyle(
      fontFamily: 'Dosis',
      fontSize: 12,
      fontWeight: FontWeight.w600,
    );
    double _iconSize = 12;
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Opportunities Page',
        ),
      ),
      body: SingleChildScrollView(
        controller: _scrollController,
        child: StateBuilder<OpportunityState>(
          observe: () => _opportunitiesStateRM,
          builder: (_, model) {
            return Column(
              children: [
                ...model.state.opportunities.map(
                  (opportunity) => GestureDetector(
                    onTap: () {
                      Navigator.pushNamed(context, opportunityDetailRoute,
                          arguments: opportunity);
                    },
                    child: Column(
                      children: [
                        Image.asset("assets/images/test_image.png"),
                        Container(
                          padding:
                              EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                          child: Text("${opportunity.title}"),
                        ),
                        OpportunityLinksWidget(
                          categoryName: opportunity.category.name,
                          views: opportunity.id.toString(),
                          deadline: opportunity.deadline,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  @override
  // TODO: implement wantKeepAlive
  bool get wantKeepAlive => true;
}
