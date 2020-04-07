import React, { Component } from 'react';
import {Redirect,Link} from 'react-router-dom';
import {update_product} from '../products';

class Edit extends Component {

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

    async componentDidMount(){
        let productId = this.props.match.params.id;
        const url = `http://localhost:8080/product/${productId}`;
        const response = await fetch(url);
        const data = await response.json();
        this.setState({
            loading:false,
            product_name:data.product.product_name,
            product_code:data.product.product_code,
            id:data.product._id
        });
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
        let id = this.state.id;
        update_product(product,id)
        .then(data => {
            if(data.error){
                this.setState({error:data.error,loading:false});
            }
            else
            {
                this.setState({redirectToReferer:true});
            }
        });
    };

    render() {

        if(this.state.redirectToReferer){
            return <Redirect to="/products/listing" />
        }

        return (
            <div className="container">
                <br />
                <Link className="btn btn-raised btn-primary" to="">Back</Link>
                <h2 className="mt-5 mb-5">Edit the product</h2>

                <div className="alert alert-primary" style={{display:this.state.error?"":"none"}}>
                    {this.state.error}
                </div>

                {this.state.loading? <div className="jumbotron text-center">
                    <h2>Loading...</h2>
                    </div>:

                <form>
                    <div className="form-group">
                        <label className="text-muted">Product Name</label>
                        <input type="text" onChange={this.handleChange("product_name")} value={this.state.product_name} className="form-control" />
                    </div>
                    <div className="form-group">
                        <label className="text-muted">Product Code</label>
                        <input type="text" onChange={this.handleChange("product_code")} value={this.state.product_code} className="form-control" />
                    </div>
                    <button onClick={this.clickSubmit} className="btn btn-raised btn-primary">Update</button>
                </form>
                }

            </div>
        );
    }
}

export default Edit;
