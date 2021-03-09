import 'package:flutter/material.dart';
import 'package:forsat/application/models/auth/sign_up_form_model.dart';
import 'package:forsat/router/route_constants.dart';
import 'package:forsat/values/branding_color.dart';
import 'package:forsat/values/images.dart';
import 'package:forsat/widgets/show_snackbar.dart';
import 'package:states_rebuilder/states_rebuilder.dart';

class SignUpPage extends StatefulWidget {
  @override
  _SignUpPageState createState() => _SignUpPageState();
}

class _SignUpPageState extends State<SignUpPage> {
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
          inject: [Inject<SignUpFormModel>(() => SignUpFormModel())],
          builder: (context) {
            final _singletonSignUpFormModel = RM.get<SignUpFormModel>();
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
                  StateBuilder<SignUpFormModel>(
                    shouldRebuild: (signUpFormModel) => true,
                    builder: (context, signUpFormModel) {
                      return TextFormField(
                        onChanged: (String firstName) {
                          signUpFormModel.setState(
                              (state) => state.setFirstName(firstName),
                              catchError: true);
                        },
                        decoration: InputDecoration(
                          errorText: signUpFormModel.hasError
                              ? signUpFormModel.error.message
                              : null,
                          prefixIcon: Icon(Icons.person),
                          hintText: 'Enter your first name',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30)),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(15),
                  StateBuilder<SignUpFormModel>(
                    shouldRebuild: (signUpFormModel) => true,
                    builder: (context, signUpFormModel) {
                      return TextFormField(
                        onChanged: (String lastName) {
                          signUpFormModel.setState(
                              (state) => state.setLastName(lastName),
                              catchError: true);
                        },
                        decoration: InputDecoration(
                          errorText: signUpFormModel.hasError
                              ? signUpFormModel.error.message
                              : null,
                          prefixIcon: Icon(Icons.person),
                          hintText: 'Enter your last name',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30)),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(15),
                  StateBuilder<SignUpFormModel>(
                    shouldRebuild: (signUpFormModel) => true,
                    builder: (context, signUpFormModel) {
                      return TextFormField(
                        onChanged: (String email) {
                          signUpFormModel.setState(
                              (state) => state.setEmail(email),
                              catchError: true);
                        },
                        decoration: InputDecoration(
                          errorText: signUpFormModel.hasError
                              ? signUpFormModel.error.message
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
                  StateBuilder<SignUpFormModel>(
                    shouldRebuild: (signUpFormModel) => true,
                    builder: (context, signUpFormModel) {
                      return TextFormField(
                        onChanged: (String password) {
                          signUpFormModel.setState(
                              (state) => state.setPassword(password),
                              catchError: true);
                        },
                        obscureText: true,
                        decoration: InputDecoration(
                          errorText: signUpFormModel.hasError
                              ? signUpFormModel.error.message
                              : null,
                          prefixIcon: Icon(Icons.lock),
                          hintText: 'Enter your password',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30)),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(15),
                  StateBuilder<SignUpFormModel>(
                    shouldRebuild: (signUpFormModel) => true,
                    builder: (context, signUpFormModel) {
                      return TextFormField(
                        onChanged: (String password) {
                          signUpFormModel.setState(
                              (state) =>
                                  state.setPasswordConfirmation(password),
                              catchError: true);
                        },
                        obscureText: true,
                        decoration: InputDecoration(
                          errorText: signUpFormModel.hasError
                              ? signUpFormModel.error.message
                              : null,
                          prefixIcon: Icon(Icons.lock),
                          hintText: 'Enter password confirmation',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(30)),
                        ),
                      );
                    },
                  ),
                  buildSizedBox(25),
                  StateBuilder(
                    observe: () => _singletonSignUpFormModel,
                    builder: (_, model) {
                      return MaterialButton(
                        onPressed: () {
                          if (!_singletonSignUpFormModel.state.validateData()) {
                            showSnackBar(
                                context: context,
                                message: 'Data is invalid',
                                color: Colors.red);
                          } else {
                            _singletonSignUpFormModel.setState(
                                (signUpFormState) =>
                                    signUpFormState.submitSignUp());
                          }
                        },
                        color: brandingColor,
                        height: 55,
                        shape: StadiumBorder(),
                        child: Center(
                          child: Text(
                            "Sign Up",
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
                        'Already have an account?',
                        style: TextStyle(),
                      ),
                      FlatButton(
                        onPressed: () {
                          Navigator.pushNamed(context, signInRoute);
                        },
                        child: Text(
                          'Sign In',
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
