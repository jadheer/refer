import React, { Component } from 'react';
import {signup} from '../auth';

class Signup extends Component {

    constructor(){
        super();
        this.state = {
            name:"",
            email:"",
            password:"",
            error:"",
            open:false
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
        const {name,email,password} = this.state;
        const user = {
            name : name,
            email : email,
            password : password
        };
        signup(user)
        .then(data => {
            if(data.error){
                this.setState({error:data.error});
            }
            else
            {
                this.setState({
                    name:"",
                    email:"",
                    password:"",
                    error:"",
                    open:true
                });
            }
        });
    };

    render() {
        return (
            <div className="container">
                <h2 className="mt-5 mb-5">Signup</h2>

                <div className="alert alert-primary" style={{display:this.state.error?"":"none"}}>
                    {this.state.error}
                </div>

                <div className="alert alert-info" style={{display:this.state.open?"":"none"}}>
                    New account is successfully created, please signin
                </div>

                <form>
                    <div className="form-group">
                        <label className="text-muted">Name</label>
                        <input onChange={this.handleChange("name")} type="text" value={this.state.name} className="form-control" />
                    </div>
                    <div className="form-group">
                        <label className="text-muted">Email</label>
                        <input type="email" onChange={this.handleChange("email")} value={this.state.email} className="form-control" />
                    </div>
                    <div className="form-group">
                        <label className="text-muted">Password</label>
                        <input type="password" onChange={this.handleChange("password")} value={this.state.password} className="form-control" />
                    </div>
                    <button onClick={this.clickSubmit} className="btn btn-raised btn-primary">Register</button>
                </form>

            </div>
        );
    }
}

export default Signup;
