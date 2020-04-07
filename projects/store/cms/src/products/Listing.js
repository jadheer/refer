import React, { Component } from 'react';
import {Redirect,Link} from 'react-router-dom';
import {delete_product} from '../products';

class Listing extends Component {

    constructor(){
        super();
        this.state = {
            loading:true,
            products:null
        }
    }

    async componentDidMount(){
        const url = "http://localhost:8080/products/listing";
        const response = await fetch(url);
        const data = await response.json();
        this.setState({
            loading:false,
            products:data.products
        });
    }

    onDelete(id){
        this.setState({
            loading:true
        });
        delete_product(id)
        .then(data => {
            if(data.error){
                this.setState({error:data.error,loading:false});
            }
            else
            {
                this.componentDidMount();
            }
        });
    }

    render() {

        return (
            <div className="container">
                <h2 className="mt-5 mb-5">All Products</h2>

                <div className="alert alert-primary" style={{display:this.state.error?"":"none"}}>
                    {this.state.error}
                </div>

                {
                    this.state.loading? <div className="jumbotron text-center">
                    <h2>Loading...</h2>
                    </div>
                    :
                    (
                        <div>
                            <table className="table">
                              <thead>
                                <tr>
                                  <th scope="col">#</th>
                                  <th scope="col">Product Name</th>
                                  <th scope="col">Product Code</th>
                                  <th scope="col">Edit</th>
                                  <th scope="col">Delete</th>
                                </tr>
                              </thead>
                              <tbody>
                                {this.state.products.map((item,key) =>
                                    <tr key={key}>
                                      <th scope="row">{key+1}</th>
                                      <td>{item.product_name}</td>
                                      <td>#{item.product_code}</td>
                                      <td><Link to={`/product/edit/${item._id}`} className="btn btn-raised btn-primary">Edit</Link></td>
                                      {/*<td><Link to={`/product/edit/${item._id}`} className="btn btn-raised btn-primary">Delete</Link></td>*/}
                                      <td><button onClick={() => this.onDelete(item._id)} className="btn btn-raised btn-primary">Delete</button></td>
                                    </tr>
                                )}
                              </tbody>
                            </table>
                        </div>
                    )
                }

            </div>
        );
    }
}

export default Listing;
