import { HardhatUserConfig } from "hardhat/config";
import "@nomicfoundation/hardhat-toolbox";
import dotenv from 'dotenv'
dotenv.config()


const { PRIVATE_KEY, NETWORK, INFURA_API_KEY} =
  process.env
const hasCustomNetwork = NETWORK && NETWORK !== 'hardhat'
if (hasCustomNetwork) {
  if (!PRIVATE_KEY) {
    throw new Error('PRIVATE_KEY not set')
  }
}

const API_TEMPLATE_INFURA = 'https://{{network}}.infura.io/v3/{{key}}'

let  provider_url = API_TEMPLATE_INFURA.replace('{{network}}', NETWORK!).replace(
    '{{key}}',
    INFURA_API_KEY!
  )

console.log(PRIVATE_KEY)

const config: HardhatUserConfig = {
  solidity: {
    version: '0.8.19',
    settings: {
      viaIR: false,
      optimizer: {
        enabled: true,
        runs: 500
      }
    }
  },
  defaultNetwork: NETWORK,
  networks: {
    hardhat: {},
    arbitrumSepolia:{
      url: provider_url,
      // uncomment to make tx go faster
      // gasPrice: 450000000000,
      accounts: [PRIVATE_KEY!]
    } 
  },
  etherscan: {
    apiKey: {
      arbitrumSepolia: process.env.ARBISCAN_API_KEY!,
    }
  }
};

export default config;
