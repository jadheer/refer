import 'package:flutter/material.dart';
import 'package:forsat/application/models/auth/sign_in_form_model.dart';
import 'package:forsat/router/route_constants.dart';
import 'package:forsat/values/branding_color.dart';
import 'package:forsat/values/images.dart';
import 'package:forsat/widgets/show_snackbar.dart';
import 'package:states_rebuilder/states_rebuilder.dart';

class SignInPage extends StatefulWidget {
  @override
  _SignInPageState createState() => _SignInPageState();
}

class _SignInPageState extends State<SignInPage> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0.0,
        brightness: Brightness.light,
        automaticallyImplyLeading: false,
      ),
      body: Builder(builder: (context) {
        return Injector(
          inject: [Inject<SignInFormModel>(() => SignInFormModel())],
          builder: (context) {
            final _singletonSignInFormModel = RM.get<SignInFormModel>();
            return Container(
              padding: EdgeInsets.all(16),
              child: ListView(
                children: [
                  Container(
                    height: 250,
                    child: Center(
                      child: Image.asset(Images.logo),
                    ),
                  ),
                  StateBuilder<SignInFormModel>(
                    shouldRebuild: (signInFormModel) => true,
                    builder: (context, signInFormModel) {
                      return TextFormField(
                        onChanged: (String email) {
                          signInFormModel.setState(
                              (state) => state.setEmail(email),
                              catchError: true);
                        },
                        decoration: InputDecoration(
                          errorText: signInFormModel.hasError
                              ? signInFormModel.error.message
                              : null,
                          prefixIcon: Icon(Icons.email),
                          hintText: 'Enter your email',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30)),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(15),
                  StateBuilder<SignInFormModel>(
                    shouldRebuild: (signInFormModel) => true,
                    builder: (context, signInFormModel) {
                      return TextFormField(
                        onChanged: (String password) {
                          signInFormModel.setState(
                              (state) => state.setPassword(password),
                              catchError: true);
                        },
                        obscureText: true,
                        decoration: InputDecoration(
                          errorText: signInFormModel.hasError
                              ? signInFormModel.error.message
                              : null,
                          prefixIcon: Icon(Icons.lock),
                          hintText: 'Enter your password',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30)),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(25),
                  StateBuilder(
                    observe: () => _singletonSignInFormModel,
                    builder: (_, model) {
                      return MaterialButton(
                        onPressed: () {
                          if (!_singletonSignInFormModel.state.validateData()) {
                            showSnackBar(
                                context: context,
                                message: 'Data is invalid',
                                color: Colors.red);
                          } else {
                            _singletonSignInFormModel.setState(
                                (signInFormState) async {
                              await signInFormState.submitSignIn();
                              Navigator.pushNamed(context, homeRoute);
                            }, onError: (context, error) {
                              showSnackBar(
                                  context: context,
                                  message: error.message,
                                  color: Colors.red);
                            });
                          }
                        },
                        color: brandingColor,
                        height: 55,
                        shape: StadiumBorder(),
                        child: Center(
                          child: Text(
                            "Sign In",
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 30,
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(25),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Text(
                        'Don\'t have an account?',
                        style: TextStyle(),
                      ),
                      FlatButton(
                        onPressed: () {
                          Navigator.pushNamed(context, signUpRoute);
                        },
                        child: Text(
                          'Sign Up',
                          style: TextStyle(color: brandingColor),
                        ),
                      )
                    ],
                  )
                ],
              ),
            );
          },
        );
      }),
    );
  }

  SizedBox buildSizedBox(double height) {
    return SizedBox(
      height: height,
    );
  }
}
