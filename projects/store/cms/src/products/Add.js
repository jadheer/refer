import React, { Component } from 'react';
import {Redirect} from 'react-router-dom';
import {add_product} from '../products';

class Add extends Component {

    constructor(){
        super();
        this.state = {
            product_name:"",
            product_code:"",
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
        const {product_name,product_code} = this.state;
        const product = {
            product_name : product_name,
            product_code : product_code
        };
        add_product(product)
        .then(data => {
            if(data.error){
                this.setState({error:data.error,loading:false});
            }
            else
            {
                this.setState({redirectToReferer:true,loading:false});
            }
        });
    };

    render() {

        if(this.state.redirectToReferer){
            return <Redirect to="/products/listing" />
        }

        return (
            <div className="container">
                <h2 className="mt-5 mb-5">Add new product</h2>

                <div className="alert alert-primary" style={{display:this.state.error?"":"none"}}>
                    {this.state.error}
                </div>

                {this.state.loading? <div className="jumbotron text-center">
                    <h2>Loading...</h2>
                    </div>:""}

                <form>
                    <div className="form-group">
                        <label className="text-muted">Product Name</label>
                        <input type="text" onChange={this.handleChange("product_name")} value={this.state.product_name} className="form-control" />
                    </div>
                    <div className="form-group">
                        <label className="text-muted">Product Code</label>
                        <input type="text" onChange={this.handleChange("product_code")} value={this.state.product_code} className="form-control" />
                    </div>
                    <button onClick={this.clickSubmit} className="btn btn-raised btn-primary">Save</button>
                </form>

            </div>
        );
    }
}

export default Add;
