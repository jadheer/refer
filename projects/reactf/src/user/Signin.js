import React, { Component } from 'react';
import {Redirect} from 'react-router-dom';
import {signin, authenticate} from '../auth';

class Signin extends Component {

    constructor(){
        super();
        this.state = {
            email:"",
            password:"",
            error:"",
            redirectToReferer:false,
            loading:false
        }
    }

    handleChange = (param) => (event) => {
        this.setState({error:""});
        this.setState({
            [param]:event.target.value
        })
    };

    clickSubmit = event => {
        event.preventDefault();
        this.setState({loading:true});
        const {email,password} = this.state;
        const user = {
            email : email,
            password : password
        };
        signin(user)
        .then(data => {
            if(data.error){
                this.setState({error:data.error,loading:false});
            }
            else
            {
                // Authenticate
                authenticate(data,()=>{
                    this.setState({redirectToReferer:true});
                });
            }
        });
    };

    render() {

        if(this.state.redirectToReferer){
            return <Redirect to="/" />
        }

        return (
            <div className="container">
                <h2 className="mt-5 mb-5">SignIn</h2>

                <div className="alert alert-primary" style={{display:this.state.error?"":"none"}}>
                    {this.state.error}
                </div>

                {this.state.loading? <div className="jumbotron text-center">
                    <h2>Loading...</h2>
                    </div>:""}

                <form>
                    <div className="form-group">
                        <label className="text-muted">Email</label>
                        <input type="email" onChange={this.handleChange("email")} value={this.state.email} className="form-control" />
                    </div>
                    <div className="form-group">
                        <label className="text-muted">Password</label>
                        <input type="password" onChange={this.handleChange("password")} value={this.state.password} className="form-control" />
                    </div>
                    <button onClick={this.clickSubmit} className="btn btn-raised btn-primary">Sign In</button>
                </form>

            </div>
        );
    }
}

export default Signin;
