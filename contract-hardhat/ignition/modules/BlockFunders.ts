import { buildModule } from "@nomicfoundation/hardhat-ignition/modules";


const Module = buildModule("BlockFundersModule", (m) => {

  const contract = m.contract("BlockFunders");

  return { contract };
});

export default Module;
