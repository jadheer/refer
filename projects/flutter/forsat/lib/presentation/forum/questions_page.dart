import 'package:flutter/material.dart';
import 'package:forsat/application/state/forum_state.dart';
import 'package:states_rebuilder/states_rebuilder.dart';

class QuestionsPage extends StatefulWidget {
  @override
  _QuestionsPageState createState() => _QuestionsPageState();
}

class _QuestionsPageState extends State<QuestionsPage>
    with AutomaticKeepAliveClientMixin {
  final _forumStateRM = RM.get<ForumState>();
  ScrollController _scrollController;

  @override
  void didChangeDependencies() {
    _getNewQuestions();
    _scrollController = ScrollController();
    _scrollController.addListener(() {
      double currentPosition = _scrollController.position.pixels;
      double maxScrollExtend = _scrollController.position.maxScrollExtent;

      if (currentPosition >= maxScrollExtend && !_forumStateRM.state.loading) {
        _getNewQuestions();
      }
    });

    super.didChangeDependencies();
  }

  void _getNewQuestions() {
    _forumStateRM.setState((forumState) => forumState.getAllQuestions());
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Questions Page',
        ),
      ),
      body: SingleChildScrollView(
        controller: _scrollController,
        child: StateBuilder<ForumState>(
          observe: () => _forumStateRM,
          builder: (_, model) {
            return Column(
              children: [
                ...model.state.questions.map((question) => Container(
                      padding: EdgeInsets.symmetric(horizontal: 15),
                      child: Column(
                        children: [
                          Container(
                            margin: EdgeInsets.only(top: 20),
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 30,
                                      backgroundImage: AssetImage(
                                          "assets/images/test_image.png"),
                                    ),
                                    SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            "${question.createdBy.firstName}",
                                            style: TextStyle(
                                                fontSize: 16,
                                                fontWeight: FontWeight.bold),
                                          ),
                                          Text("${question.createdBy.email}"),
                                        ],
                                      ),
                                    ),
                                    Text("2 days ago"),
                                  ],
                                ),
                                SizedBox(
                                  height: 10,
                                ),
                                Text(
                                  "${question.question}",
                                  maxLines: 2,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                                SizedBox(
                                  height: 10,
                                ),
                                Container(
                                  height: 1,
                                  color: Colors.black26,
                                ),
                              ],
                            ),
                          )
                        ],
                      ),
                    )),
              ],
            );
          },
        ),
      ),
    );
  }

  @override
  bool get wantKeepAlive => true;
}
